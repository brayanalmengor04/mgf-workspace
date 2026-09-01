const MAX_DIMENSION = 1600;
const JPEG_QUALITY = 0.82;
const SKIP_BELOW_BYTES = 280_000;

async function compressBudgetScanImage(file) {
    if (!file?.type?.startsWith('image/')) {
        return file;
    }

    if (file.size <= SKIP_BELOW_BYTES) {
        return file;
    }

    const bitmap = await createImageBitmap(file);
    const scale = Math.min(1, MAX_DIMENSION / Math.max(bitmap.width, bitmap.height));
    const width = Math.max(1, Math.round(bitmap.width * scale));
    const height = Math.max(1, Math.round(bitmap.height * scale));

    const canvas = document.createElement('canvas');
    canvas.width = width;
    canvas.height = height;

    const ctx = canvas.getContext('2d');
    ctx.drawImage(bitmap, 0, 0, width, height);
    bitmap.close();

    const blob = await new Promise((resolve) => {
        canvas.toBlob(resolve, 'image/jpeg', JPEG_QUALITY);
    });

    if (!blob || blob.size >= file.size) {
        return file;
    }

    const baseName = file.name.replace(/\.[^.]+$/, '') || 'presupuesto';

    return new File([blob], `${baseName}.jpg`, {
        type: 'image/jpeg',
        lastModified: Date.now(),
    });
}

function registerBudgetScanBanner() {
    if (!window.Alpine) {
        return;
    }

    window.Alpine.data('budgetScanBanner', () => ({
        uploadProgress: 0,
        isUploading: false,

        async pickFile(event, wire) {
            const input = event.target;
            const file = input.files?.[0];

            input.value = '';

            if (!file || !wire) {
                return;
            }

            this.isUploading = true;
            this.uploadProgress = 0;

            try {
                const prepared = await compressBudgetScanImage(file);

                wire.upload(
                    'scanImage',
                    prepared,
                    () => {
                        this.isUploading = false;
                        this.uploadProgress = 100;
                    },
                    () => {
                        this.isUploading = false;
                        this.uploadProgress = 0;
                    },
                    (event) => {
                        this.uploadProgress = event.detail?.progress ?? 0;
                    },
                );
            } catch {
                this.isUploading = false;
                this.uploadProgress = 0;
            }
        },
    }));
}

document.addEventListener('alpine:init', registerBudgetScanBanner);

if (window.Alpine) {
    registerBudgetScanBanner();
}
