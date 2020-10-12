/**
 * @TODO Transpile or refactor ES201x syntax?
 * @TODO Add proper commments.
 */

const Deployer = ($) => {
  const PAGE_SELECTOR = ".js-simply-static-deploy";
  const STATUS_SELECTOR = ".js-status";
  const TRIGGER_BUTTONS_SELECTOR = '.js-trigger-button';
  const ERROR_CONTAINER_SELECTOR = ".js-error-container";
  const ERROR_MESSAGE_SELECTOR = ".js-error-message";

  const $page = $(PAGE_SELECTOR);
  
  const $deployForm = $page.find(`form[data-type="ssd-deploy-form"]`);
  const $triggerButtons = $page.find(TRIGGER_BUTTONS_SELECTOR);
  const $errorContainer = $page.find(ERROR_CONTAINER_SELECTOR);
  const $errorMessage = $page.find(ERROR_MESSAGE_SELECTOR);
  const $statusContainer = $page.find(STATUS_SELECTOR);

  const enableTriggerButtons = () => $triggerButtons.prop("disabled", false);
  const disableTriggerButtons = () => $triggerButtons.prop("disabled", true);

  const hideError = () => $errorContainer.attr("aria-hidden", "true");
  const showError = (message) => {
    $errorMessage.html(message);
    $errorContainer.attr("aria-hidden", "false");
  };

  const formGetValue = (key) => {
    return $deployForm.serializeArray()
      .find(input => input.name === key)
      .value;
  }

  const post = (action) => {
    return new Promise((resolve, reject) => {
      $.ajax({
        method: "POST",
        url: action,
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

  const updateStatus = (status, message) => {
      $statusContainer.text(message);
  }

  const handleDeploySubmit = (e) => {
    e.preventDefault();
    disableTriggerButtons();
    hideError();

    post($deployForm.prop('action'))
      .then(response => {
        updateStatus('busy', 'Deployment in progress...');
      })
      .catch(error => {
        showError(error);
        enableTriggerButtons();
      });
  };

  return {
    init() {
      $deployForm.on("submit", handleDeploySubmit);
    },
  };
};

jQuery(function ($) {
  const deployer = Deployer($);
  deployer.init();
});
