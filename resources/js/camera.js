/**
 * Photo source picker for the scanner. Phones use the native camera app (best focus and
 * exposure); devices without a touch screen get a live in-page camera because the HTML
 * `capture` attribute is ignored there. Every source ends up in the form's single file input.
 */
export default (inputRef = 'image') => ({
    preview: null,
    submitting: false,
    streaming: false,
    stream: null,
    facingMode: 'environment',
    cameraError: '',

    get prefersNativeCamera() {
        return window.matchMedia('(pointer: coarse)').matches;
    },

    takePhoto() {
        this.cameraError = '';

        if (this.prefersNativeCamera || ! navigator.mediaDevices?.getUserMedia) {
            this.$refs.cameraInput.click();

            return;
        }

        this.startCamera();
    },

    chooseFromGallery() {
        this.cameraError = '';
        this.$refs.galleryInput.click();
    },

    useFile(file) {
        if (! file) {
            return;
        }

        const transfer = new DataTransfer();
        transfer.items.add(file);
        this.$refs[inputRef].files = transfer.files;
        this.cameraError = '';

        if (this.preview) {
            URL.revokeObjectURL(this.preview);
        }

        this.preview = URL.createObjectURL(file);
    },

    async startCamera() {
        this.cameraError = '';

        try {
            this.stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: { ideal: this.facingMode }, width: { ideal: 1920 }, height: { ideal: 1440 } },
                audio: false,
            });
            this.streaming = true;
            await this.$nextTick();
            this.$refs.video.srcObject = this.stream;
        } catch (error) {
            this.stopCamera();
            this.cameraError = error.name === 'NotAllowedError'
                ? 'Camera access was blocked. Allow it in your browser settings, or upload a photo instead.'
                : 'No camera is available on this device. Upload a photo instead.';
        }
    },

    async switchCamera() {
        this.facingMode = this.facingMode === 'environment' ? 'user' : 'environment';
        this.stopCamera();
        await this.startCamera();
    },

    snap() {
        const video = this.$refs.video;
        const canvas = document.createElement('canvas');
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        canvas.getContext('2d').drawImage(video, 0, 0);

        canvas.toBlob((blob) => {
            this.useFile(new File([blob], `scan-${Date.now()}.jpg`, { type: 'image/jpeg' }));
            this.stopCamera();
        }, 'image/jpeg', 0.9);
    },

    stopCamera() {
        this.stream?.getTracks().forEach((track) => track.stop());
        this.stream = null;
        this.streaming = false;
    },

    destroy() {
        this.stopCamera();
    },
});
