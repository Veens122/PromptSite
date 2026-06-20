<!DOCTYPE html>
<html>

<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Project Preview</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }

        .toolbar {
            margin-bottom: 15px;
        }

        .toolbar button,
        .toolbar a {
            padding: 8px 14px;
            margin-right: 10px;
            text-decoration: none;
            border: 1px solid #ccc;
            background: #f5f5f5;
            cursor: pointer;
        }

        iframe {
            width: 100%;
            height: 500px;
            border: 1px solid #ccc;
        }
    </style>
</head>

<body>

    <h2>Project Preview</h2>

    <div class="toolbar">
        <button onclick="publishProject({{ $project->id }})">
            Publish
        </button>

        <button id="edit-btn"
            class="bg-gray-700 hover:bg-gray-600 text-white font-semibold px-4 py-2 rounded-lg transition-colors">
            Edit
        </button>


        <a href="/project/{{ $project->id }}/download" target="_blank">
            Download
        </a>

        <button id="save-edit-btn" class="hidden" >Save</button>
        
    </div>

    <hr>

    <!-- SINGLE SAFE IFRAME -->
    {{-- <iframe 
    src="/render/{{ $project->slug }}"
    sandbox="allow-scripts allow-same-origin allow-forms allow-popups">
</iframe> --}}

<div id="color-toolbar" style="
    position: fixed;
    bottom: 20px;
    right: 20px;
    background: #111;
    padding: 10px;
    border-radius: 10px;
    display: none;
    z-index: 9999;
">
    <input type="color" id="color-picker" />
    
    <button id="apply-text-color">Text</button>
    <button id="apply-bg-color">Background</button>
    <button id="apply-border-color">Border</button>
</div>

    <iframe id="website-preview"></iframe>

    {{-- Website Preview --}}
    {{-- The script below works with the ifram with the id "website-preview" --}}

    <script>
        let previewReady = false;

        document.addEventListener("DOMContentLoaded", () => {
            const iframe = document.getElementById("website-preview");
            const html = @json($project->html);

            iframe.srcdoc = html;

            iframe.onload = () => {
                previewReady = true;
                console.log("Preview ready ✅");
            };

            // SET PROJECT ID (VERY IMPORTANT FOR SAVE)
            window.currentProjectId = {{ $project->id }};
        });
    </script>

{{-- Script for edit button --}}
<script>
let selectedElement = null;

document.getElementById("edit-btn")?.addEventListener("click", () => {
    const iframe = document.getElementById("website-preview");

    if (!iframe || !iframe.contentDocument) {
        alert("Preview not ready");
        return;
    }

    const doc = iframe.contentDocument;

    // ✅ ENABLE TEXT EDITING
    doc.body.contentEditable = true;
    doc.body.style.outline = "2px dashed #06b6d4";

    // ✅ SHOW COLOR TOOLBAR
    const toolbar = document.getElementById("color-toolbar");
    if (toolbar) toolbar.style.display = "block";

    // ✅ HANDLE ELEMENT SELECTION
    doc.body.addEventListener("click", (e) => {
        e.preventDefault();
        e.stopPropagation();

        selectedElement = e.target;

        // remove previous highlights
        doc.querySelectorAll("*").forEach(el => el.style.outline = "none");

        // highlight selected
        selectedElement.style.outline = "2px solid red";
    });

    // ✅ IMAGE REPLACE (CLEAN + SERVER UPLOAD)
    doc.querySelectorAll("img").forEach(img => {
        img.style.cursor = "pointer";

        img.onclick = (e) => {
            e.preventDefault();
            e.stopPropagation();

            const input = document.createElement("input");
            input.type = "file";
            input.accept = "image/*";

            input.onchange = async (e) => {
                const file = e.target.files[0];
                if (!file) return;

                // 🔥 Instant preview (safe small preview)
                const reader = new FileReader();
                reader.onload = () => {
                    img.src = reader.result;
                };
                reader.readAsDataURL(file);

                // ✅ Upload to server (IMPORTANT: avoid base64 saving issue)
                const formData = new FormData();
                formData.append("image", file);

                try {
                    const res = await fetch("/upload-image", {
                        method: "POST",
                        headers: {
                            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: formData
                    });

                    const data = await res.json();

                    if (data.url) {
                        img.src = data.url; // ✅ replace with real URL
                    }

                } catch (err) {
                    console.error(err);
                    alert("Image upload failed");
                }
            };

            input.click();
        };
    });

    console.log("Edit mode FULLY ACTIVE ✅");
});
</script>

{{-- color picker script --}}
<script>
const colorPicker = document.getElementById("color-picker");

document.getElementById("apply-text-color").onclick = () => {
    if (!selectedElement) return alert("Select an element first");
    selectedElement.style.color = colorPicker.value;
};

document.getElementById("apply-bg-color").onclick = () => {
    if (!selectedElement) return alert("Select an element first");
    selectedElement.style.backgroundColor = colorPicker.value;
};

document.getElementById("apply-border-color").onclick = () => {
    if (!selectedElement) return;
    selectedElement.style.borderColor = colorPicker.value;
};
</script>


    <script>
        async function publishProject(projectId) {
            const csrf = document.querySelector('meta[name="csrf-token"]').content;

            const res = await fetch(`/project/${projectId}/publish`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf
                }
            });

            const data = await res.json();

            if (data.success) {
                alert("Website Published!");
                window.open(data.url, '_blank');
            }
        }
    </script>
    <script>
        window.currentProjectSlug = "{{ $project->slug ?? '' }}";
    </script>

    {{-- Script for save button --}}
    <script>
document.getElementById("save-edit-btn")?.addEventListener("click", async () => {
    const iframe = document.getElementById("website-preview");

    if (!iframe || !iframe.contentDocument) {
        alert("Nothing to save");
        return;
    }

    const doc = iframe.contentDocument;

    // TURN OFF EDIT MODE
    doc.body.contentEditable = false;
    doc.body.style.outline = "none";

    // REMOVE IMAGE EVENTS
    doc.querySelectorAll("img").forEach(img => {
        img.style.cursor = "default";
        img.onclick = null;
    });

    // CLEAN EDIT ATTRIBUTES
    doc.querySelectorAll('[contenteditable]').forEach(el => {
        el.removeAttribute('contenteditable');
    });

    const html = doc.documentElement.outerHTML;

    try {
        const res = await fetch(`/project/${window.currentProjectId}/update-html`, {
            method: "PUT",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ html })
        });

        const data = await res.json();

        if (data.success) {
            alert("Saved successfully!");
        }

    } catch (err) {
        console.error(err);
        alert("Failed to save changes");
    }
});
</script>






</body>

</html>
