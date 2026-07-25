<div class="modal-backdrop" id="editTransferModal" style="display:none" aria-hidden="true" onclick="if(event.target===this)closeModal('editTransferModal')">
    <div class="modal modal-lg" role="dialog" aria-modal="true" aria-labelledby="editTransferModalTitle">
        <div class="modal-head">
            <div><span class="eyebrow">Operations</span><h2 id="editTransferModalTitle">Edit transfer</h2></div>
            <button type="button" class="icon-button" onclick="closeModal('editTransferModal')" aria-label="Close transfer form">&times;</button>
        </div>
        <form method="POST" id="editTransferForm" action="" class="modal-body modal-form-grid">
            @csrf
            @method('PATCH')
            <label>Group name <span class="required">*</span>
                <input type="text" name="group_name" data-transfer-field="group_name" placeholder="e.g. The Morgan family" required maxlength="255">
            </label>
            <label>Guests <span class="required">*</span>
                <input type="number" name="pax" data-transfer-field="pax" min="1" max="999" placeholder="2" required>
            </label>
            <label>Transfer date <span class="required">*</span>
                <input type="date" name="arrival_date" data-transfer-field="arrival_date" required>
            </label>
            <label>Pickup time <span class="required">*</span>
                <input type="time" name="pickup_time" data-transfer-field="pickup_time" required>
            </label>
            <label>Pickup location <span class="required">*</span>
                <input type="text" name="pickup_location" data-transfer-field="pickup_location" placeholder="Kilimanjaro Airport" required maxlength="255">
            </label>
            <label>Drop-off location <span class="required">*</span>
                <input type="text" name="dropoff_location" data-transfer-field="dropoff_location" placeholder="Arusha Coffee Lodge" required maxlength="255">
            </label>
            <label>Departure date
                <input type="date" name="departure_date" data-transfer-field="departure_date">
            </label>
            <label>Flight number
                <input type="text" name="flight_number" data-transfer-field="flight_number" placeholder="e.g. PW 412" maxlength="40">
            </label>
            <label>Contact number
                <input type="text" name="contact_number" data-transfer-field="contact_number" placeholder="+255 700 000 000" maxlength="80">
            </label>
            <label>Supplier
                <select name="supplier_id" data-transfer-field="supplier_id">
                    <option value="">Leave unassigned</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                    @endforeach
                </select>
            </label>
            <label>Guide
                <select name="guide_id" data-transfer-field="guide_id">
                    <option value="">Leave unassigned</option>
                    @foreach($guides as $guide)
                        <option value="{{ $guide->id }}">{{ $guide->name }}</option>
                    @endforeach
                </select>
            </label>
            <label>Vehicle
                <select name="vehicle_id" data-transfer-field="vehicle_id">
                    <option value="">Leave unassigned</option>
                    @foreach($vehicles as $vehicle)
                        <option value="{{ $vehicle->id }}">{{ $vehicle->registration_number }} · {{ $vehicle->capacity }} guests</option>
                    @endforeach
                </select>
            </label>
            <label>Status
                <select name="status" data-transfer-field="status">
                    @foreach($transferStatusOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="modal-field-wide">Notes
                <textarea name="notes" data-transfer-field="notes" rows="3" maxlength="1000" placeholder="Pickup notes, luggage or guest details"></textarea>
            </label>
            <div class="modal-foot modal-field-wide">
                <button type="button" class="button button-secondary" onclick="closeModal('editTransferModal')">Cancel</button>
                <button type="submit" class="button button-primary">Save transfer</button>
            </div>
        </form>
    </div>
</div>
