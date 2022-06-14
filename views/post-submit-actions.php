<style>
.ssd-publishbox {
}
.ssd-publishbox[aria-hidden="true"] {
    visibility: hidden;
}
.ssd-publishbox__status[data-status="ready"] {
    color: gray;
}

.ssd-publishbox__status[data-status="busy"] {
    color: orange;
}

.ssd-publishbox__status[data-status="error"] {
    color: red;
}

.ssd-publishbox__recursive {
    padding: 8px 10px;
}
</style>
<!--
    Note: since this partial will be injected in to the edit post action,
    we should tell tell the button it should belong to another form
-->
<div
    id="ssd-single-deploy-submit-container"
    class="misc-pub-section ssd-publishbox"
    aria-hidden="true"
    data-poll-status-endpoint="<?= $this->poll_status_endpoint ?>"
    >
    <button
        type="submit"
        form="<?= $this->form_id ?>"
        <?= $this->status === 'busy' ? 'disabled' : '' ?>
        >
        Deploy
    </button>
    <span
        class="ssd-publishbox__status"
        data-status="<?= $this->status ?>"
        >
        <?= $this->status_message ?>
    </span>
    <div class="ssd-publishbox__recursive">
        <input type="checkbox" name="recursive" form="<?= $this->form_id ?>">
        Recursive deploy
        </input>
    </div>
</div>

<script>
const StaticDeploySingle = ($) => {
    const $container = $('#ssd-single-deploy-submit-container');
    $container.attr('aria-hidden', false);
    const $button = $container.find('button[type=submit]');
    const $form = $($button.prop('form'));
    const $statusElement = $container.find('.ssd-publishbox__status');

    const POLL_STATUS_ENDPOINT = $container.data('poll-status-endpoint');
    let pollStatusInterval;

    const formGetValue = (key) => {
        $element = $form.serializeArray()
            .find(input => input.name === key);
        if ($element) {
            return $element.value
        }
        return null;
    }

    const updateStatus = (status, message) => {
        $statusElement.text(message);
        $statusElement.data('status', status);
    }

    const post = (action, data) => {
        return new Promise((resolve, reject) => {
        $.ajax({
            method: 'POST',
            url: action,
            data: data,
            beforeSend: (xhr) => {
            xhr.setRequestHeader("X-WP-Nonce", formGetValue('_wpnonce'));
            },
        })
            .done(resolve)
            .fail((response) => {
            reject(
                response.responseJSON
                ? response.responseJSON.message
                : response.statusText
            );
            });
        });
    };

    const enableSubmitButton = () => $button.prop("disabled", false);
    const disableSubmitButton = () => $button.prop("disabled", true);

    const pollStatus = () => {
        post(POLL_STATUS_ENDPOINT)
            .then((response) => {
                if (response === 'busy') {
                    updateStatus('busy', 'Deployment in progress...');
                } else {
                    updateStatus('ready', 'Ready for deployment');
                    enableSubmitButton();
                }
            })
            .catch((error) => {
                window.clearInterval(pollStatusInterval);
                updateStatus('error', error);
            });
    }

    const handleDeploySubmit = (e) => {
        e.preventDefault();
        disableSubmitButton();
        updateStatus('busy', 'Deployment in progress...');
        post(
            $form.prop('action'),
            {
                post_id: formGetValue('post_id'),
                recursive: (formGetValue('recursive') ? 1 : 0)
            }
            )
            .then( (response) => {
                pollStatusInterval = window.setInterval(pollStatus, 2 * 1000);
            })
            .catch((error) => {
                window.clearInterval(pollStatusInterval);
                updateStatus('error', error);
                enableSubmitButton();
            })
    };

    return {
        init: () => {
            $form.on('submit', handleDeploySubmit);

        }
    };
}

jQuery(function ($) {
    const deployer = StaticDeploySingle($);
    deployer.init();
});
</script>
