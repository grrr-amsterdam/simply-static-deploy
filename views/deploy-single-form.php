<!-- 
    Submit button is placed outside the form container,
    check views/post-submit-actions.php
-->
<form 
    id="<?= $this->form->id ?>"
    action="<?= $this->form->action ?>" 
    method="<?= $this->form->method ?>"
    >
    <input type="hidden" name="post_id" value="<?= $this->post->ID ?>" >
</form>