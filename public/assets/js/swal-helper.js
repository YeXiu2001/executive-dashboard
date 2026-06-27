window.SwalHelper = {
    // =========================
    // TOAST INSTANCE (REUSABLE)
    // =========================
    toast: Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 2500,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.onmouseenter = Swal.stopTimer;
            toast.onmouseleave = Swal.resumeTimer;
        }
    }),

    // =========================
    // TOAST METHODS
    // =========================
    toastSuccess(message = 'Success') {
        return this.toast.fire({
            icon: 'success',
            title: message
        });
    },

    toastError(message = 'Something went wrong') {
        return this.toast.fire({
            icon: 'error',
            title: message
        });
    },

    toastInfo(message = 'Info') {
        return this.toast.fire({
            icon: 'info',
            title: message
        });
    },

    toastWarning(message = 'Warning') {
        return this.toast.fire({
            icon: 'warning',
            title: message
        });
    },

    // =========================
    // MODAL ALERTS
    // =========================
    success(message = 'Success', title = 'Success!') {
        return Swal.fire({
            title,
            text: message,
            icon: 'success',
            timer: 2000,
            showConfirmButton: false
        });
    },

    error(message = 'Something went wrong', title = 'Error!') {
        return Swal.fire({
            title,
            text: message,
            icon: 'error'
        });
    },

    // =========================
    // HANDLE LIVEWIRE RESPONSE
    // =========================
    handle(response, { useToast = false } = {}) {
        if (!response) {
            return useToast
                ? this.toastError('No response from server')
                : this.error('No response from server');
        }

        if (response.status === 'success') {
            return useToast
                ? this.toastSuccess(response.message)
                : this.success(response.message);
        }

        return useToast
            ? this.toastError(response.message || 'Operation failed')
            : this.error(response.message || 'Operation failed');
    },

    // =========================
    // CONFIRM
    // =========================
    async confirm({
        title = 'Are you sure?',
        text = 'This action cannot be undone.',
        confirmText = 'Yes, proceed',
        cancelText = 'Cancel',
        icon = 'warning'
    } = {}) {
        const result = await Swal.fire({
            title,
            text,
            icon,
            showCancelButton: true,
            confirmButtonText: confirmText,
            cancelButtonText: cancelText,
            reverseButtons: true
        });

        return result.isConfirmed;
    },

    // =========================
    // CONFIRM + ACTION
    // =========================
    async confirmAction({
        action,
        id = null,
        title,
        text,
        confirmText,
        successMessage = null,
        errorMessage = null,
        useToast = false
    }) {
        const confirmed = await this.confirm({
            title,
            text,
            confirmText
        });

        if (!confirmed) return;

        try {
            let response = id !== null
                ? await action(id)
                : await action();

            if (response && response.status) {
                return this.handle(response, { useToast });
            }

            return useToast
                ? this.toastSuccess(successMessage || 'Action successful')
                : this.success(successMessage || 'Action successful');

        } catch (e) {
            return useToast
                ? this.toastError(errorMessage || 'Action failed')
                : this.error(errorMessage || 'Action failed');
        }
    }
};