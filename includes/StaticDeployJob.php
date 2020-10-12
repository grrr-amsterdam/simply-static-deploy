<?php

namespace Grrr\SimplyStaticDeploy;

use Garp\Functional as f;
use Grrr\SimplyStaticDeploy\Tasks\InvalidateTask;
use Grrr\SimplyStaticDeploy\Tasks\RestoreInitialOptionsTask;
use Grrr\SimplyStaticDeploy\Tasks\SetupSingleTask;
use Grrr\SimplyStaticDeploy\Tasks\StoreInitialOptionsTask;
use Grrr\SimplyStaticDeploy\Tasks\SyncTask;
use Simply_Static\Cancel_Task;
use Simply_Static\Fetch_Urls_Task;
use Simply_Static\Options;
use Simply_Static\Plugin;
use Simply_Static\Setup_Task;
use Simply_Static\Transfer_Files_Locally_Task;
use Simply_Static\Util;
use Simply_Static\Wrapup_Task;
use WP_Error;

class StaticDeployJob extends \WP_Background_Process {

    const CLEAR_FILTER = 'simply_static_deploy_clear_directory';

    protected $options;

    protected $current_task;

    protected $task_list;

    protected $task_class_mapping = [
        'store_initial_options' => StoreInitialOptionsTask::class,
        'setup' => Setup_Task::class,
        'setup_single' => SetupSingleTask::class,
        'fetch_urls' => Fetch_Urls_Task::class,
        'transfer_files_locally' => Transfer_Files_Locally_Task::class,
        'wrapup' => Wrapup_Task::class,
        'restore_initial_options' => RestoreInitialOptionsTask::class,
        'sync' => SyncTask::class,
        'invalidate' => InvalidateTask::class,
        'cancel' => Cancel_Task::class,
    ];

    public function __construct() {
        $this->options = Options::instance();

        $this->task_list = $this->compose_task_list();

        if (!static::is_job_done()) {
            register_shutdown_function(array($this, 'shutdown_handler'));
        }

        parent::__construct();
    }

    /**
     * Helper method for starting the Archive_Creation_Job
     * @return boolean true if we were able to successfully start generating an archive
     */
    public function start(?int $post_id = null) {
        if (static::is_job_done()) {
            Util::debug_log("Starting a job; no job is presently running");
            // when we have a post id, we should set that somwhere, since every task does it's own request
            // with each task request, we compose the task list based on that setting
            update_option(Plugin::SLUG . '_single_deploy_id', $post_id);
            $this->task_list = $this->compose_task_list();
            Util::debug_log("Here's our task list: " . implode(', ', $this->task_list));
            global $blog_id;

            $this->task_list = $this->compose_task_list($post_id);
            $first_task = $this->task_list[0];
            $archive_name = join('-', array(Plugin::SLUG, $blog_id, time()));

            // Clear the static directory if needed.
            $this->clear_directory($this->options->get('local_dir') ?: '');

            $this->options
                ->set('archive_status_messages', array())
                ->set('archive_name', $archive_name)
                ->set('archive_start_time', Util::formatted_datetime())
                ->set('archive_end_time', null)
                ->save();

            Util::debug_log("Pushing first task to queue: " . $first_task);

            $this->push_to_queue($first_task)
                ->save()
                ->dispatch();

            return true;
        } else {
            Util::debug_log("Not starting; we're already in the middle of a job");
            // looks like we're in the middle of creating an archive...
            return false;
        }
    }

    protected function task($task_name) {
        $this->set_current_task($task_name);

        Util::debug_log("Current task: " . $task_name);

        // convert 'an_example' to 'An_Example_Task'
        $class_name = $this->resolve_task_class_name($task_name);

        // this shouldn't ever happen, but just in case...
        if (!class_exists($class_name)) {
            $this->save_status_message("Class doesn't exist: " . $class_name, 'error');
            return false;
        }

        $task = new $class_name();

        // attempt to perform the task
        try {
            Util::debug_log("Performing task: " . $task_name);
            $is_done = $task->perform();
        } catch (\Error $e) {

            Util::debug_log("Caught an error");
            return $this->error_occurred($e);
        } catch (\Exception $e) {
            Util::debug_log("Caught an exception");
            return $this->exception_occurred($e);
        }

        if (is_wp_error($is_done)) {
            // we've hit an error, time to quit
            Util::debug_log("We encountered a WP_Error");
            return $this->error_occurred($is_done);
        } else if ($is_done === true) {
            // finished current task, try to find the next one
            $next_task = $this->find_next_task();
            if ($next_task === null) {
                Util::debug_log("This task is done and there are no more tasks, time to complete the job");
                // we're done; returning false to remove item from queue
                return false;
            } else {
                Util::debug_log("We've found our next task: " . $next_task);
                // start the next task
                return $next_task;
            }
        } else { // $is_done === false
            Util::debug_log("We're not done with the " . $task_name . " task yet");
            // returning current task name to continue processing
            return $task_name;
        }

        Util::debug_log("We shouldn't have gotten here; returning false to remove the " . $task_name . " task from the queue");
        return false; // remove item from queue
    }

    /**
     * This is run at the end of the job, after task() has returned false
     * @return void
     */
    protected function complete() {
        Util::debug_log("Completing the job");
        $this->set_current_task('done');

        $end_time = Util::formatted_datetime();
        $start_time = $this->options->get('archive_start_time');
        $duration = strtotime($end_time) - strtotime($start_time);
        $time_string = gmdate("H:i:s", $duration);

        $this->options->set('archive_end_time', $end_time);

        $this->save_status_message(sprintf(__('Done! Finished in %s', 'simply-static'), $time_string));
        parent::complete();
    }

    public function get_current_task() {
        return $this->current_task;
    }

    protected function set_current_task($task_name) {
        $this->current_task = $task_name;
    }

    /**
     * Is the job done?
     * @return boolean True if done, false if not
     */
    public static function is_job_done(): bool {
        $options = Options::instance();
        $start_time = $options->get('archive_start_time');
        $end_time = $options->get('archive_end_time');
        // we're done if the start and end time are null (never run) or if
        // the start and end times are both set
        return ($start_time == null && $end_time == null) || ($start_time != null && $end_time != null);
    }

    public static function last_end_time() {
        $options = Options::instance();
        return $options->get('archive_end_time');
    }

    /**
     * Find the next task on our task list
     * @return string|null       The name of the next task, or null if none
     */
    protected function find_next_task() {
        $task_name = $this->get_current_task();
        $index = array_search($task_name, $this->task_list);
        if ($index === false) {
            return null;
        }

        $index += 1;
        if (($index) >= count($this->task_list)) {
            return null;
        } else {
            return $this->task_list[$index];
        }
    }

    /**
     * Add a message to the array of status messages for the job
     *
     * Providing a unique key for the message is optional. If one isn't
     * provided, the state_name will be used. Using the same key more than once
     * will overwrite previous messages.
     *
     * @param  string $message Message to display about the status of the job
     * @param  string $key     Unique key for the message
     * @return void
     */
    protected function save_status_message($message, $key = null) {
        $task_name = $key ?: $this->get_current_task();
        $messages = $this->options->get('archive_status_messages');
        Util::debug_log('Status message: [' . $task_name . '] ' . $message);

        $messages = Util::add_archive_status_message($messages, $task_name, $message);

        $this->options
            ->set('archive_status_messages', $messages)
            ->save();
    }

    protected function resolve_task_class_name(string $task_name) {
        return f\prop($task_name, $this->task_class_mapping);
    }

    /**
     * Add a status message about the exception and cancel the job
     * @param  Exception $exception The exception that occurred
     * @return void
     */
    protected function exception_occurred($exception) {
        Util::debug_log("An exception occurred: " . $exception->getMessage());
        Util::debug_log($exception);
        $message = sprintf(__("An exception occurred: %s", 'simply-static'), $exception->getMessage());
        $this->save_status_message($message, 'error');
        return 'cancel';
    }

    /**
     * Add a status message about the error and cancel the job
     * @param  WP_Error $wp_error The error that occurred
     * @return void
     */
    protected function error_occurred($wp_error) {
        $errorMessage = is_a($wp_error, WP_Error::class)
            ? $wp_error->get_error_message()
            : $wp_error->getMessage();

        Util::debug_log("An error occurred: " . $errorMessage);
        Util::debug_log($wp_error);
        $message = sprintf(__("An error occurred: %s", 'simply-static'), $errorMessage);
        $this->save_status_message($message, 'error');
        return 'cancel';
    }


    /**
     * Shutdown handler for fatal error reporting
     * @return void
     */
    public function shutdown_handler() {
        // Note: this function must be public in order to function properly.
        $error = error_get_last();
        // only trigger on actual errors, not warnings or notices
        if ($error && in_array($error['type'], array(E_ERROR, E_CORE_ERROR, E_USER_ERROR))) {
            $this->clear_scheduled_event();
            $this->unlock_process();
            $this->cancel_process();

            $end_time = Util::formatted_datetime();
            $this->options
                ->set('archive_end_time', $end_time)
                ->save();

            $error_message = '(' . $error['type'] . ') ' . $error['message'];
            $error_message .= ' in <b>' . $error['file'] . '</b>';
            $error_message .= ' on line <b>' . $error['line'] . '</b>';

            $message = sprintf(__("Error: %s", 'simply-static'), $error_message);
            Util::debug_log($message);
            $this->save_status_message($message, 'error');

            
            // restore initial options
            $restoreInitialOptionsTask = new RestoreInitialOptionsTask();
            $restoreInitialOptionsTask->perform();
        }
    }

    protected function compose_task_list() {
        // check if we are working on a single deploy
        $single_deploy_id = get_option(Plugin::SLUG . '_single_deploy_id');
        if ($single_deploy_id) {
            $task_list = [
                'store_initial_options',
                'setup_single',
                'fetch_urls',
                'transfer_files_locally',
                'wrapup',
                'restore_initial_options',
                'sync',
                'invalidate',
            ];
        } else {
            $task_list = [
                'store_initial_options',
                'setup',
                'fetch_urls',
                'transfer_files_locally',
                'wrapup',
                'restore_initial_options',
                'sync',
                'invalidate',
            ];
        }
        return $task_list;
    }

    /**
     * Clear the current static site directory, to make sure deleted pages
     * are not deployed again, and potentialy overwriting redirects.
     */
    private function clear_directory(string $dir): void {
        $clear = apply_filters(static::CLEAR_FILTER, false);
        if (!$clear || !$dir || !file_exists($dir)) {
            return;
        }
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $dir,
                \RecursiveDirectoryIterator::SKIP_DOTS
            ),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $fileinfo) {
            $todo = $fileinfo->isDir() ? 'rmdir' : 'unlink';
            $todo($fileinfo->getRealPath());
        }
    }
}
