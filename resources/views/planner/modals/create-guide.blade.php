<div class="modal-backdrop" id="guideModal" style="display:none" onclick="if(event.target===this)closeModal('guideModal')">
    <div class="modal">
        <div class="modal-head">
            <h2>Add guide</h2>
            <button type="button" class="icon-button" onclick="closeModal('guideModal')">&times;</button>
        </div>
        <form method="POST" action="{{ route('planner.guides.store') }}" class="modal-body">
            @csrf
            <label>Full name <span class="required">*</span>
                <input type="text" name="name" required>
            </label>
            <label>Email
                <input type="email" name="email">
            </label>
            <label>Phone
                <input type="text" name="phone">
            </label>
            <label>License number
                <input type="text" name="license_number">
            </label>
            <label>Status <span class="required">*</span>
                <select name="status" required>
                    <option value="available">Available</option>
                    <option value="on_trip">On trip</option>
                    <option value="on_leave">On leave</option>
                    <option value="unavailable">Unavailable</option>
                </select>
            </label>
            <label>Languages (comma separated)
                <input type="text" name="languages" placeholder="English, Swahili, French">
            </label>
            <label>License expiry
                <input type="date" name="license_expiry">
            </label>
            <div class="modal-foot">
                <button type="button" class="button button-secondary" onclick="closeModal('guideModal')">Cancel</button>
                <button type="submit" class="button button-primary">Save guide</button>
            </div>
        </form>
    </div>
</div>
