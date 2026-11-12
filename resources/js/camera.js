/**
 * Photo source picker and live camera scanner.
 * Supports in-browser live camera viewfinder across mobile and desktop,
 * with fallback to the device's native camera or gallery picker.
 */
export default (inputRef = 'image') => ({
    preview: null,
    submitting: false,
    streaming: false,
    stream: null,
    facingMode: 'environment',
    cameraError: '',
    selectedFile: null,

    get hasGetUserMedia() {
        return !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia);
    },

    takePhoto() {
        this.cameraError = '';
        if (this.hasGetUserMedia) {
            this.startCamera();
        } else {
            this.openNativeCamera();
        }
    },

    openNativeCamera() {
        this.cameraError = '';
        this.stopCamera();
        if (this.$refs.cameraInput) {
            this.$refs.cameraInput.value = '';
            this.$refs.cameraInput.click();
        }
    },

    chooseFromGallery() {
        this.cameraError = '';
        this.stopCamera();
        if (this.$refs.galleryInput) {
            this.$refs.galleryInput.value = '';
            this.$refs.galleryInput.click();
        }
    },

    useFile(file) {
        if (! file) {
            return;
        }

        this.selectedFile = file;
        this.cameraError = '';

        // Try setting file on the named file input via DataTransfer
        try {
            const transfer = new DataTransfer();
            transfer.items.add(file);
            if (this.$refs[inputRef]) {
                this.$refs[inputRef].files = transfer.files;
            }
        } catch (e) {
            console.warn('DataTransfer not supported, selectedFile will be submitted directly', e);
        }

        if (this.preview) {
            URL.revokeObjectURL(this.preview);
        }

        this.preview = URL.createObjectURL(file);
    },

    async startCamera() {
        this.cameraError = '';
        this.stopCamera();

        try {
            let stream;
            try {
                // Try high quality portrait/landscape resolution with back camera
                stream = await navigator.mediaDevices.getUserMedia({
                    video: {
                        facingMode: { ideal: this.facingMode },
                        width: { ideal: 1920, min: 640 },
                        height: { ideal: 1080, min: 480 },
                    },
                    audio: false,
                });
            } catch (constraintErr) {
                // Fallback to basic facingMode constraint if resolution constraints fail
                stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: { ideal: this.facingMode } },
                    audio: false,
                });
            }

            this.stream = stream;
            this.streaming = true;
            await this.$nextTick();

            const video = this.$refs.video;
            if (video) {
                video.srcObject = this.stream;
                try {
                    await video.play();
                } catch (playErr) {
                    console.warn('Video play error:', playErr);
                }
            }
        } catch (error) {
            this.stopCamera();
            console.warn('Live camera error:', error);
            if (error.name === 'NotAllowedError') {
                this.cameraError = 'Camera permission was denied. You can still take a photo using your device camera below.';
            } else {
                this.cameraError = 'Could not access the live camera. Using native camera instead.';
            }
            // Auto fallback to native camera picker so user isn't blocked
            this.openNativeCamera();
        }
    },

    async switchCamera() {
        this.facingMode = this.facingMode === 'environment' ? 'user' : 'environment';
        this.stopCamera();
        await this.startCamera();
    },

    snap() {
        const video = this.$refs.video;
        if (! video || ! video.videoWidth || ! video.videoHeight) {
            this.cameraError = 'Camera is still loading. Please wait a moment and try again.';
            return;
        }

        const canvas = document.createElement('canvas');
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

        canvas.toBlob((blob) => {
            if (blob) {
                this.useFile(new File([blob], `scan-${Date.now()}.jpg`, { type: 'image/jpeg' }));
            }
            this.stopCamera();
        }, 'image/jpeg', 0.92);
    },

    stopCamera() {
        if (this.stream) {
            this.stream.getTracks().forEach((track) => track.stop());
            this.stream = null;
        }
        this.streaming = false;
    },

    handleSubmit(event) {
        // If DataTransfer failed to populate input[name="image"] but selectedFile exists
        const input = this.$refs[inputRef];
        if (this.selectedFile && (! input || ! input.files || ! input.files.length)) {
            event.preventDefault();
            this.submitting = true;

            const form = event.target;
            const formData = new FormData(form);
            formData.set('image', this.selectedFile);

            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html,application/xhtml+xml,application/xml',
                },
            }).then((response) => {
                if (response.redirected) {
                    window.location.href = response.url;
                } else {
                    window.location.reload();
                }
            }).catch((err) => {
                this.submitting = false;
                this.cameraError = 'Submission failed. Please check your internet connection and try again.';
            });

            return;
        }

        this.submitting = true;
    },

    destroy() {
        this.stopCamera();
    },
});
