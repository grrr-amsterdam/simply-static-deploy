<!-- 
    Note: since this partial will be injected in to the edit post action, 
    we should tell tell the button it should belong to another form 
-->
<div class="misc-pub-section">
    <button 
        type="submit" 
        form="<?= $this->form_id ?>" 
        <?= $this->is_job_done ?: 'disabled' ?>
        >
        Deploy
    </button>
    <span
        >
        <?= $this->is_job_done ? '' : 'Deployment in progress...' ?>
    </span>
</div>