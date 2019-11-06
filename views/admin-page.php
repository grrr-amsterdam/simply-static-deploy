<div class="wrap js-grrr-static-site">
    <div class="card">
        <section>
            <h1><?= get_admin_page_title() ?></h1>
            <p>Generate a static version of the website, sync it to the static hosting environment, and invalidate the cache.</p>
            <div class="wp-clearfix" style="margin-bottom: 15px;">
                <form class="alignleft" data-type="all" style="margin-right: 10px;">
                    <button class="button button-primary button-large js-trigger-button" type="submit">Generate &amp; deploy</button>
                </form>
                <button class="button button-large alignleft js-tasks-toggle" aria-controls="grrr-static-site-tasks" aria-expanded="false">Show tasks</button>
            </div>
            <hr/>
            <ul class="deploy-status js-status">
                <li>
                    <strong>1. Generate: </strong>
                    <span data-type="generate">
                        <span class="is-unscheduled">
                            Not scheduled,
                            <?php if ($this->times['generate']): ?>
                                last at <?= $this->times['generate'] ?>.
                            <?php else: ?>
                                never ran.
                            <?php endif; ?>
                        </span>
                    </span>
                </li>
                <li>
                    <strong>2. Sync: </strong>
                    <span data-type="sync">
                        <span class="is-unscheduled">
                            Not scheduled,
                            <?php if ($this->times['sync']): ?>
                                last at <?= $this->times['sync'] ?>.
                            <?php else: ?>
                                never ran.
                            <?php endif; ?>
                        </span>
                    </span>
                </li>
                <li>
                    <strong>3. Invalidate: </strong>
                    <span data-type="invalidate">
                        <span class="is-unscheduled">
                            Not scheduled,
                            <?php if ($this->times['invalidate']): ?>
                                last at <?= $this->times['invalidate'] ?>.
                            <?php else: ?>
                                never ran.
                            <?php endif; ?>
                        </span>
                    </span>
                </li>
            </ul>
            <div class="deploy-error js-error-container" role="alert" aria-hidden="true">
                <h4>An error occurred</h4>
                <p class="js-error-message">...</p>
            </div>
            <hr/>
            <div class="deploy-time">
                <strong>Status: </strong>
                <span class="js-deploy-time">
                    <?= $this->in_progress
                        ? '<span class="is-in-progress">Bundle generation in progress, please wait!</span>'
                        : '<span>Nothing to report.</span>'
                    ?>
                </span>
            </div>
        </section>

        <section class="deploy-tasks" id="grrr-static-site-tasks" aria-hidden="true">
            <hr/>
            <p>Individual tasks to generate, sync and invalidate the website. These tasks are normally executed in the given order.</p>

            <form action="<?= $this->endpoints['generate'] ?>"
                method="post"
                data-type="generate">
                <button class="button js-trigger-button" type="submit">Generate site bundle</button>
            </form>

            <form action="<?= $this->endpoints['sync'] ?>"
                method="post"
                data-type="sync">
                <button class="button js-trigger-button" type="submit">Sync to S3</button>
            </form>

            <form action="<?= $this->endpoints['invalidate'] ?>"
                method="post"
                data-type="invalidate">
                <button class="button js-trigger-button" type="submit">Invalidate CloudFront</button>
            </form>
        </section>
    </div>
</div>
