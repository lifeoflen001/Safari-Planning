<div class="modal-backdrop" id="vehicleModal" style="display:none" onclick="if(event.target===this)closeModal('vehicleModal')">
    <div class="modal">
        <div class="modal-head">
            <h2>Add vehicle</h2>
            <button type="button" class="icon-button" onclick="closeModal('vehicleModal')">&times;</button>
        </div>
        <form method="POST" action="{{ route('planner.vehicles.store') }}" class="modal-body">
            @csrf
            <label>Registration number <span class="required">*</span>
                <input type="text" name="registration_number" required>
            </label>
            <label>Type <span class="required">*</span>
                <input type="text" name="type" value="4x4 safari vehicle" required>
            </label>
            <label>Capacity (guests) <span class="required">*</span>
                <input type="number" name="capacity" min="1" max="20" required>
            </label>
            <label>Status <span class="required">*</span>
                <select name="status" required>
                    <option value="available">Available</option>
                    <option value="in_service">In service</option>
                    <option value="maintenance">Maintenance</option>
                    <option value="unavailable">Unavailable</option>
                </select>
            </label>
            <label>Last service date
                <input type="date" name="last_service_at">
            </label>
            <label>Next service date
                <input type="date" name="next_service_at">
            </label>
            <div class="modal-foot">
                <button type="button" class="button button-secondary" onclick="closeModal('vehicleModal')">Cancel</button>
                <button type="submit" class="button button-primary">Save vehicle</button>
            </div>
        </form>
    </div>
</div>
