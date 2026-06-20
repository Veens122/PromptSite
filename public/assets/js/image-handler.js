// Image Handler for AI Website Builder
class ImageHandler {
    constructor() {
        this.projectId = null;
        this.replacedImages = new Set();
    }

    // Start polling for image generation status
    startPolling(projectId, previewContainer) {
        this.projectId = projectId;
        this.previewContainer = previewContainer;

        console.log(`Starting image polling for project: ${projectId}`);
        this.pollImageStatus();
    }

    async pollImageStatus() {
        if (!this.projectId) return;

        try {
            const response = await fetch(
                `/api/project/${this.projectId}/image-status`,
                {
                    headers: {
                        "X-CSRF-TOKEN": document.querySelector(
                            'meta[name="csrf-token"]'
                        ).content,
                        Accept: "application/json",
                    },
                }
            );

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const data = await response.json();

            if (data.images && Array.isArray(data.images)) {
                this.replacePlaceholderImages(data.images);
            }

            // Continue polling if not all images are done
            if (
                data.status === "processing" ||
                data.images_completed < data.images_total
            ) {
                setTimeout(() => this.pollImageStatus(), 2000);
            } else if (data.status === "completed") {
                console.log("All images generated successfully!");
                this.showSuccessNotification();
            }
        } catch (error) {
            console.error("Polling error:", error);
            // Retry after 5 seconds on error
            setTimeout(() => this.pollImageStatus(), 5000);
        }
    }

    replacePlaceholderImages(images) {
        images.forEach((image) => {
            if (this.replacedImages.has(image.token) || !image.url) return;

            // Find all placeholder images with matching data attribute
            const placeholders = this.previewContainer.querySelectorAll(
    `[data-image="${image.token}"]`
);

            placeholders.forEach((placeholder) => {
                // Replace placeholder with actual image
                if (placeholder.tagName === "IMG") {
                    placeholder.src = image.url;
                    placeholder.classList.remove("bg-gray-200");
                    placeholder.classList.add("generated-image");
                } else if (placeholder.hasAttribute("data-image")) {
                    // For background images
                    placeholder.style.backgroundImage = `url('${image.url}')`;
                    placeholder.classList.remove("bg-gray-300");
                }

                // Add loading animation
                placeholder.classList.add("animate-fade-in");
                this.replacedImages.add(image.token);

                console.log(
                    `Replaced image token ${image.token} with ${image.url}`
                );
            });
        });
    }

    showSuccessNotification() {
        const notification = document.createElement("div");
        notification.className =
            "fixed top-4 right-4 bg-green-500 text-white px-4 py-2 rounded-lg shadow-lg z-50 animate-slide-in";
        notification.innerHTML = `
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span>All images generated successfully!</span>
            </div>
        `;

        document.body.appendChild(notification);

        // Remove notification after 3 seconds
        setTimeout(() => {
            notification.classList.add("animate-slide-out");
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
}

// Initialize image handler
window.imageHandler = new ImageHandler();

// CSS for animations
const style = document.createElement("style");
style.textContent = `
    .animate-fade-in {
        animation: fadeIn 0.5s ease-in-out;
    }
    
    .animate-slide-in {
        animation: slideIn 0.3s ease-out;
    }
    
    .animate-slide-out {
        animation: slideOut 0.3s ease-in forwards;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
    
    .generated-image {
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    
    .image-placeholder {
        background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
        background-size: 200% 100%;
        animation: loading 1.5s infinite;
    }
    
    @keyframes loading {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }
`;
document.head.appendChild(style);
