<dialog class="modal" id="confirm-dialog" aria-labelledby="confirm-title" aria-describedby="confirm-text">
    <form method="dialog">
        <div class="modal__body">
            <h2 class="modal__title" id="confirm-title">Are you sure?</h2>
            <p class="muted" id="confirm-text" style="margin:0"></p>
        </div>
        <div class="modal__actions">
            <button class="btn btn--outline" value="cancel">Cancel</button>
            <button class="btn btn--danger" value="confirm" data-confirm-ok>Confirm</button>
        </div>
    </form>
</dialog>
