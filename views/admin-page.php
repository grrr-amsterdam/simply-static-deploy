<div class="wrap js-simply-static-deploy">

    <!-- see https://wordpress.stackexchange.com/a/220735 -->
    <h2 style="display: none;"></h2>

    <div class="card">
        <section>
            <h1><?= get_admin_page_title() ?></h1>
            <p>Generate a static version of the website, sync it to the static hosting environment, and invalidate the cache.</p>
            <div class="wp-clearfix" style="margin-bottom: 15px;">
                <form 
                    class="alignleft" 
                    data-type="ssd-deploy-form"
                    action="<?= $this->form->action ?>" 
                    method="<?= $this->form->method ?>" 
                    style="margin-right: 10px;"
                    >
                    <?= wp_nonce_field('wp_rest') ?>
                    <button 
                        class="button button-primary button-large js-trigger-button" 
                        type="submit"
                        <?= $this->in_progress ? 'disabled' : '' ?>
                        >
                        Generate &amp; deploy
                    </button>
                </form>
            </div>
            <hr />
            <span class="js-status"><?= $this->in_progress
                ? 'Deployment in progress...'
                : 'Last deployment finished at: ' .
                    $this->last_end_time ?></span>
            <div class="deploy-error js-error-container" role="alert" aria-hidden="true">
                <h4>An error occurred</h4>
                <p class="js-error-message">...</p>
            </div>
            <hr />
            <div class="deploy-time">
                <strong>Status: </strong>
                <span>
                    <?= $this->in_progress
                        ? '<span class="is-in-progress">Bundle generation in progress, please wait!</span>'
                        : '<span>Nothing to report.</span>' ?>
                </span>
            </div>
        </section>
    </div>

</div>