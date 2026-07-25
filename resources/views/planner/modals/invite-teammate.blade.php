<div class="modal-backdrop" id="inviteTeammateModal" style="display:none" aria-hidden="true" onclick="if(event.target===this)closeModal('inviteTeammateModal')">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="inviteTeammateModalTitle">
        <div class="modal-head">
            <div><span class="eyebrow">Team & access</span><h2 id="inviteTeammateModalTitle">Add teammate</h2></div>
            <button type="button" class="icon-button" onclick="closeModal('inviteTeammateModal')" aria-label="Close teammate form">&times;</button>
        </div>
        <form method="POST" action="{{ route('planner.team.invite') }}" class="modal-body">
            @csrf
            <p class="modal-intro">Create a workspace profile now. They can set their password when an invitation email is connected.</p>
            <label>Full name <span class="required">*</span>
                <input type="text" name="name" placeholder="e.g. Asha Mollel" required maxlength="255">
            </label>
            <label>Work email <span class="required">*</span>
                <input type="email" name="email" placeholder="asha@example.com" required maxlength="255">
            </label>
            <label>Job title
                <input type="text" name="job_title" placeholder="Safari coordinator" maxlength="120">
            </label>
            <label>Access role <span class="required">*</span>
                <select name="role" required>
                    <option value="viewer">Viewer · read-only access</option>
                    <option value="commenter">Commenter · review and comment</option>
                    <option value="editor">Editor · coordinate operations</option>
                    <option value="admin">Admin · manage workspace</option>
                </select>
            </label>
            <label>Timezone
                <select name="timezone">
                    <option value="Africa/Dar_es_Salaam">East Africa Time</option>
                    <option value="UTC">UTC</option>
                    <option value="Europe/London">London</option>
                    <option value="America/New_York">New York</option>
                </select>
            </label>
            <div class="modal-foot">
                <button type="button" class="button button-secondary" onclick="closeModal('inviteTeammateModal')">Cancel</button>
                <button type="submit" class="button button-primary">Add teammate</button>
            </div>
        </form>
    </div>
</div>
