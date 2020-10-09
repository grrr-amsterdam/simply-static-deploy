<style>
.ssd-publishbox {
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
</style>
<!-- 
    Note: since this partial will be injected in to the edit post action, 
    we should tell tell the button it should belong to another form 
-->
<div 
    id="ssd-single-deploy-submit-container"
    class="misc-pub-section sdd-publishbox" 
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
</div>

<script>
const StaticDeploySingle = ($) => {
    const $container = $('#ssd-single-deploy-submit-container');
    const $button = $container.find('button[type=submit]');
    const $form = $($button.prop('form'));
    const $statusElement = $container.find('.ssd-publishbox__status');
    
    const POLL_STATUS_ENDPOINT = $container.data('poll-status-endpoint');

    const formGetValue = (key) => {
        return $form.serializeArray()
            .find(input => input.name === key)
            .value;
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

    const handleDeploySubmit = (e) => {
        e.preventDefault();
        disableSubmitButton();
        post(
            $form.prop('action'), 
            {
              post_id: formGetValue('post_id'),
            }
            )
            .then( (response) => {
                updateStatus('busy', 'Deployment in progress...');
            })
            .catch((error) => {
                updateStatus('error', error);
                enableSubmitButton();
            })
    };
    
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
                updateStatus('error', error);
            });
    }

    return {
        init: () => {
            $form.on('submit', handleDeploySubmit);
            window.setInterval(pollStatus, 1000);
        }
    };
}

jQuery(function ($) {
    const deployer = StaticDeploySingle($);
    deployer.init();
});
</script>