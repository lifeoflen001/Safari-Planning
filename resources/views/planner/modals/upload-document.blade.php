<div class="modal-backdrop" id="documentModal" style="display:none" onclick="if(event.target===this)closeModal('documentModal')">
    <div class="modal">
        <div class="modal-head">
            <h2>Upload document</h2>
            <button type="button" class="icon-button" onclick="closeModal('documentModal')">&times;</button>
        </div>
        <form method="POST" action="{{ route('planner.documents.upload') }}" enctype="multipart/form-data" class="modal-body">
            @csrf
            <label>File <span class="required">*</span>
                <input type="file" name="file" required>
            </label>
            <label>Link to booking <span class="required">*</span>
                <select name="booking_id" required>
                    <option value="">Select a booking</option>
                    @foreach($bookings as $booking)
                        <option value="{{ $booking->id }}">{{ $booking->reference }} · {{ $booking->group_name }}</option>
                    @endforeach
                </select>
            </label>
            <label>Display name
                <input type="text" name="name" placeholder="Auto-filled from file name">
            </label>
            <div class="modal-foot">
                <button type="button" class="button button-secondary" onclick="closeModal('documentModal')">Cancel</button>
                <button type="submit" class="button button-primary">Upload document</button>
            </div>
        </form>
    </div>
</div>
