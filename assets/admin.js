/**
 * @TODO Transpile or refactor ES201x syntax?
 * @TODO Add proper commments.
 */

const Deployer = $ => {

  const PAGE_SELECTOR = '.js-simply-static-deploy';
  const STATUS_SELECTOR = '.js-status';
  const TRIGGER_BUTTONS_SELECTOR = '.js-trigger-button';
  const DEPLOY_TIME_SELECTOR = '.js-deploy-time';
  const TASKS_TOGGLE_SELECTOR = '.js-tasks-toggle';
  const ERROR_CONTAINER_SELECTOR = '.js-error-container';
  const ERROR_MESSAGE_SELECTOR = '.js-error-message';

  const VARS = window.SIMPLY_STATIC_DEPLOY;
  const TASKS = VARS.tasks;
  const API_VARS = VARS.api;
  const ENDPOINTS = API_VARS.endpoints;
  const NONCE = API_VARS.nonce;

  const QUEUED_TASKS = [];
  const PREVIOUSLY_QUEUED_TASKS = [];

  const STATUS_LABELS = {
    unscheduled: 'Not scheduled.',
    scheduled: 'Scheduled.',
    current: 'In progress... (keep this window open)',
    done: 'Done.',
    failed: 'Failed.',
    processing: 'Processing in background.',
  };

  const $page = $(PAGE_SELECTOR);
  const $deployForm = $page.find(`form[data-type="all"]`);

  const $deployInBackgroundForm = $page.find(`form[data-type="background"]`);

  const $taskForms = $page.find(`
    form[data-type="generate"],
    form[data-type="sync"],
    form[data-type="invalidate"]
  `);
  const $triggerButtons = $page.find(TRIGGER_BUTTONS_SELECTOR);
  const $tasksToggle = $page.find(TASKS_TOGGLE_SELECTOR);
  const $statusList = $page.find(STATUS_SELECTOR);
  const $deployTime = $page.find(DEPLOY_TIME_SELECTOR);
  const $errorContainer = $page.find(ERROR_CONTAINER_SELECTOR);
  const $errorMessage = $page.find(ERROR_MESSAGE_SELECTOR);

  const capitalize = string => string.charAt(0).toUpperCase() + string.slice(1);

  const enableTriggerButtons = () => $triggerButtons.prop('disabled', false);
  const disableTriggerButtons = () => $triggerButtons.prop('disabled', true);

  const hideError = () => $errorContainer.attr('aria-hidden', 'true');
  const showError = message => {
    $errorMessage.html(message);
    $errorContainer.attr('aria-hidden', 'false');
    $statusList.find('span[class="is-current"]').html(createTaskStatusTemplate('failed'));
  };

  const post = action => {
    return new Promise((resolve, reject) => {
      $.ajax({
        method: 'POST',
        url: action,
        beforeSend: xhr => {
          xhr.setRequestHeader('X-WP-Nonce', NONCE);
        },
      })
        .done(resolve)
        .fail(response => {
          reject(response.responseJSON
            ? response.responseJSON.message
            : response.statusText);
        });
    });
  };

  const getTaskStatus = (task, current, isSingle) => {
    if (task === current && QUEUED_TASKS.includes(task)) {
      return 'current';
    }
    if (isSingle && !current && task === getPreviousTask()) {
      return 'done';
    }
    return isSingle
      ? 'unscheduled'
      : QUEUED_TASKS.includes(task)
        ? 'scheduled'
        : 'done';
  };

  const createTaskStatusTemplate = status => {
    return `<span class="is-${status}">${STATUS_LABELS[status]}</span>`;
  };

  const updateStatus = (current, isSingle = false) => {
    $.each(TASKS, (index, task) => {
      const status = getTaskStatus(task, current, isSingle);
      const template = createTaskStatusTemplate(status);
      $statusList.find(`[data-type=${task}]`).html(template);
    });
  };

  const performTask = task => {
    return new Promise((resolve, reject) => {
      post(ENDPOINTS[task])
        .then(response => resolve(task))
        .catch(error => {
          showError(error);
          enableTriggerButtons();
        });
    });
  };

  const getNextTask = () => QUEUED_TASKS.length ? QUEUED_TASKS[0] : null;

  const getPreviousTask = () => PREVIOUSLY_QUEUED_TASKS.length
    ? PREVIOUSLY_QUEUED_TASKS[PREVIOUSLY_QUEUED_TASKS.length - 1]
    : null;

  const performQueuedTasks = (isSingle = false) => {
    const next = getNextTask();
    updateStatus(next, isSingle);
    hideError();
    if (!next & !isSingle) {
      $deployTime.html(`Just deployed, <a href="${VARS.website}" target="_blank">view site</a>.`);
    }
    if (!next) {
      enableTriggerButtons();
      return;
    }
    performTask(next).then(last => {
      unqueueTask(last);
      performQueuedTasks(isSingle);
    });
  };

  const queueTasks = tasks => Object.assign(QUEUED_TASKS, tasks);

  const unqueueTask = task => {
    const index = QUEUED_TASKS.indexOf(task);
    if (index > -1) {
      QUEUED_TASKS.splice(index, 1);
    }
    PREVIOUSLY_QUEUED_TASKS.push(task);
  };

  const triggerBuild = () => {
    queueTasks(TASKS);
    performQueuedTasks();
  };

  const triggerSingleTask = task => {
    queueTasks([task]);
    performQueuedTasks(true);
  }

  const handleDeploySubmit = e => {
    e.preventDefault();
    disableTriggerButtons();
    triggerBuild();
  };

  const handleBackgroundDeploySubmit = e => {
    e.preventDefault();
    queueTasks(['background_deploy']);
    performQueuedTasks()
    updateStatus()

    post(ENDPOINTS['background_deploy'])
      .then(response => {
        const template = createTaskStatusTemplate('processing');
        $statusList.find(`[data-type="background_deploy"]`).html(template);
      })
      .catch(error => {
        showError(error);
        enableTriggerButtons();
      });
  }

  const handleTaskSubmit = e => {
    e.preventDefault();
    disableTriggerButtons();
    triggerSingleTask($(e.target).attr('data-type'));
  };

  const handleTasksToggle = e => {
    const $tasks = $page.find(`#${$tasksToggle.attr('aria-controls')}`);
    const isHidden = $tasks.attr('aria-hidden') === 'true';
    $tasksToggle.text(isHidden ? 'Hide tasks' : 'Show tasks');
    $tasksToggle.attr('aria-expanded', !!isHidden);
    $tasks.attr('aria-hidden', !isHidden);
  };

  return {
    init() {
      $deployForm.on('submit', handleDeploySubmit);
      $taskForms.on('submit', handleTaskSubmit);
      $tasksToggle.on('click', handleTasksToggle);

      $deployInBackgroundForm.on('submit', handleBackgroundDeploySubmit);
    },
  };
};

jQuery(function($) {
  const deployer = Deployer($);
  deployer.init();
});
