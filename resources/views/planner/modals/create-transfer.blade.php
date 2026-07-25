<div class="modal-backdrop" id="transferModal" style="display:none" aria-hidden="true" onclick="if(event.target===this)closeModal('transferModal')">
    <div class="modal modal-lg" role="dialog" aria-modal="true" aria-labelledby="transferModalTitle">
        <div class="modal-head">
            <div><span class="eyebrow">Operations</span><h2 id="transferModalTitle">Schedule transfer</h2></div>
            <button type="button" class="icon-button" onclick="closeModal('transferModal')" aria-label="Close transfer form">&times;</button>
        </div>
        <form method="POST" action="{{ route('planner.transfers.store') }}" class="modal-body modal-form-grid">
            @csrf
            <label>Group name <span class="required">*</span>
                <input type="text" name="group_name" placeholder="e.g. The Morgan family" required maxlength="255">
            </label>
            <label>Guests <span class="required">*</span>
                <input type="number" name="pax" min="1" max="999" placeholder="2" required>
            </label>
            <label>Transfer date <span class="required">*</span>
                <input type="date" name="arrival_date" value="{{ now()->toDateString() }}" required>
            </label>
            <label>Pickup time <span class="required">*</span>
                <input type="time" name="pickup_time" value="08:00" required>
            </label>
            <label>Pickup location <span class="required">*</span>
                <input type="text" name="pickup_location" placeholder="Kilimanjaro Airport" required maxlength="255">
            </label>
            <label>Drop-off location <span class="required">*</span>
                <input type="text" name="dropoff_location" placeholder="Arusha Coffee Lodge" required maxlength="255">
            </label>
            <label>Departure date
                <input type="date" name="departure_date">
            </label>
            <label>Flight number
                <input type="text" name="flight_number" placeholder="e.g. PW 412" maxlength="40">
            </label>
            <label>Contact number
                <input type="text" name="contact_number" placeholder="+255 700 000 000" maxlength="80">
            </label>
            <label>Supplier
                <select name="supplier_id">
                    <option value="">Leave unassigned</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                    @endforeach
                </select>
            </label>
            <label>Guide
                <select name="guide_id">
                    <option value="">Leave unassigned</option>
                    @foreach($guides as $guide)
                        <option value="{{ $guide->id }}">{{ $guide->name }}</option>
                    @endforeach
                </select>
            </label>
            <label>Vehicle
                <select name="vehicle_id">
                    <option value="">Leave unassigned</option>
                    @foreach($vehicles as $vehicle)
                        <option value="{{ $vehicle->id }}">{{ $vehicle->registration_number }} · {{ $vehicle->capacity }} guests</option>
                    @endforeach
                </select>
            </label>
            <label class="modal-field-wide">Notes
                <textarea name="notes" rows="3" maxlength="1000" placeholder="Pickup notes, luggage or guest details"></textarea>
            </label>
            <div class="modal-foot modal-field-wide">
                <button type="button" class="button button-secondary" onclick="closeModal('transferModal')">Cancel</button>
                <button type="submit" class="button button-primary">Schedule transfer</button>
            </div>
        </form>
    </div>
</div>
