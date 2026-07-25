<div class="modal-backdrop" id="confirmActionModal" style="display:none" aria-hidden="true" onclick="if(event.target===this)closeModal('confirmActionModal')">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="confirmActionTitle">
        <div class="modal-head">
            <div><span class="eyebrow">Confirm action</span><h2 id="confirmActionTitle" data-confirm-title>Are you sure?</h2></div>
            <button type="button" class="icon-button" onclick="closeModal('confirmActionModal')" aria-label="Close confirmation">&times;</button>
        </div>
        <form method="POST" id="confirmActionForm" action="">
            @csrf
            <div class="modal-body"><p class="modal-intro" data-confirm-message>This action will update the planning record.</p></div>
            <div class="modal-foot">
                <button type="button" class="button button-ghost" onclick="closeModal('confirmActionModal')">Keep it</button>
                <button type="submit" class="button button-primary" data-confirm-submit>Continue</button>
            </div>
        </form>
    </div>
</div>
