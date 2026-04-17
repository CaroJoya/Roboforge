<?php
require_once 'session.php';
require_once 'tags_data.php';
requireLogin();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Upload Robot Design - ROBOFORGE</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, sans-serif; 
            background: #fafafa; 
            min-height: 100vh;
        }
        .container { 
            max-width: 600px; 
            margin: 40px auto; 
            background: white; 
            border-radius: 12px; 
            box-shadow: 0 4px 20px rgba(0,0,0,0.1); 
            overflow: hidden;
        }
        .header { 
            padding: 30px 40px; 
            border-bottom: 1px solid #eee; 
            text-align: center;
        }
        .upload-area { 
            padding: 40px; 
            text-align: center;
            border-bottom: 1px solid #eee;
        }
        .upload-zone {
            width: 100%; 
            height: 200px; 
            border: 3px dashed #ddd; 
            border-radius: 12px; 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            justify-content: center; 
            cursor: pointer;
            transition: all 0.3s;
            background: #f8f9fa;
        }
        .upload-zone:hover, .upload-zone.dragover {
            border-color: #1a1a1a; 
            background: #f0f0f0;
        }
        .upload-icon { font-size: 48px; color: #666; margin-bottom: 16px; }
        .upload-text { color: #666; margin-bottom: 8px; }
        .upload-subtext { color: #999; font-size: 14px; }
        .form-group { 
            padding: 0 40px 40px; 
        }
        .form-group label { 
            display: block; 
            margin-bottom: 8px; 
            font-weight: 500; 
            color: #333;
        }
        .form-group input, .form-group textarea, .form-group select { 
            width: 100%; 
            padding: 16px; 
            border: 1px solid #ddd; 
            border-radius: 8px; 
            font-family: inherit;
            font-size: 16px;
            transition: border-color 0.2s;
        }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus {
            outline: none;
            border-color: #1a1a1a;
        }
        .form-group textarea { 
            resize: vertical; 
            min-height: 120px;
            max-height: 200px;
        }
        .tags-select {
            background: #f8f9fa;
            cursor: pointer;
        }
        .suggest-btn {
            background: #f0f0f0;
            color: #333;
            border: 1px solid #ddd;
            padding: 8px 16px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 14px;
            margin-top: 10px;
            transition: all 0.2s;
        }
        .suggest-btn:hover {
            background: #e0e0e0;
        }
        .upload-btn { 
            width: 100%; 
            padding: 18px; 
            background: #1a1a1a; 
            color: white; 
            border: none; 
            border-radius: 8px; 
            font-size: 16px; 
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
            margin-top: 20px;
        }
        .upload-btn:hover { background: #333; }
        .upload-btn:disabled { background: #ccc; cursor: not-allowed; }
        #preview { 
            max-width: 100%; 
            max-height: 300px; 
            border-radius: 8px; 
            margin-top: 20px;
            display: none;
        }
        .selected-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
        }
        .tag-badge {
            background: #1a1a1a;
            color: white;
            padding: 4px 12px;
            border-radius: 16px;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container">
        <div class="header">
            <h1 style="font-size: 28px; font-weight: 700; color: #1a1a1a;">Share Robot Design</h1>
            <p style="color: #666; margin-top: 4px;">Upload images of your robotics projects</p>
        </div>
        
        <form id="uploadForm" enctype="multipart/form-data">
            <div class="upload-area">
                <div class="upload-zone" onclick="document.getElementById('imageInput').click()">
                    <div class="upload-icon">📸</div>
                    <div class="upload-text">Click to upload robot design</div>
                    <div class="upload-subtext">JPG, PNG up to 50MB</div>
                    <img id="preview" src="" alt="Preview">
                </div>
                <input type="file" id="imageInput" name="image" accept="image/jpeg,image/png" style="display: none;" required>
            </div>
            
            <div class="form-group">
                <label>Select Tags (up to 3)</label>
                <select id="tagSelect" class="tags-select">
                    <option value="">Choose a tag...</option>
                    <?php foreach ($tagsLibrary as $tagData): ?>
                        <option value="<?php echo $tagData['tag']; ?>">
                            #<?php echo $tagData['tag']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div id="selectedTagsContainer" class="selected-tags"></div>
                <input type="hidden" name="tags" id="tagsInput" value="">
                
                <label style="margin-top: 24px;">Caption</label>
                <textarea name="caption" id="captionInput" placeholder="Describe your robot design, materials used, and how it works..." maxlength="500"></textarea>
                
                <button type="button" class="suggest-btn" id="suggestCaptionBtn">
                    💡 Generate Suggested Caption
                </button>
                
                <button type="submit" class="upload-btn" id="submitBtn" disabled>
                    Post Design
                </button>
            </div>
        </form>
    </div>

    <script>
    const form = document.getElementById('uploadForm');
    const imageInput = document.getElementById('imageInput');
    const preview = document.getElementById('preview');
    const submitBtn = document.getElementById('submitBtn');
    const uploadZone = document.querySelector('.upload-zone');
    const tagSelect = document.getElementById('tagSelect');
    const selectedTagsContainer = document.getElementById('selectedTagsContainer');
    const tagsInput = document.getElementById('tagsInput');
    const captionInput = document.getElementById('captionInput');
    const suggestBtn = document.getElementById('suggestCaptionBtn');
    
    let selectedTags = [];
    
    // Tag library for suggestions (PHP data passed to JS)
    const tagsLibrary = <?php echo json_encode($tagsLibrary); ?>;
    
    // Function to update displayed tags
    function updateTagsDisplay() {
        selectedTagsContainer.innerHTML = '';
        selectedTags.forEach(tag => {
            const badge = document.createElement('div');
            badge.className = 'tag-badge';
            badge.textContent = '#' + tag;
            badge.style.cursor = 'pointer';
            badge.onclick = () => removeTag(tag);
            selectedTagsContainer.appendChild(badge);
        });
        tagsInput.value = selectedTags.join(',');
        
        // Enable/disable submit button based on image and tags
        submitBtn.disabled = !imageInput.files.length || selectedTags.length === 0;
    }
    
    function removeTag(tag) {
        selectedTags = selectedTags.filter(t => t !== tag);
        updateTagsDisplay();
    }
    
    // Add tag from dropdown
    tagSelect.addEventListener('change', function() {
        const tag = this.value;
        if (tag && !selectedTags.includes(tag) && selectedTags.length < 3) {
            selectedTags.push(tag);
            updateTagsDisplay();
        }
        this.value = ''; // Reset dropdown
    });
    
    // Generate suggested caption based on selected tags
    suggestBtn.addEventListener('click', function() {
        if (selectedTags.length === 0) {
            alert('Please select at least one tag first!');
            return;
        }
        
        const firstTag = selectedTags[0];
        const tagData = tagsLibrary.find(t => t.tag === firstTag);
        
        if (tagData && tagData.suggested_caption) {
            captionInput.value = tagData.suggested_caption;
        } else {
            captionInput.value = `Check out my ${selectedTags[0]} robot project! #${selectedTags[0]}`;
        }
    });
    
    // Drag & drop
    uploadZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadZone.classList.add('dragover');
    });
    uploadZone.addEventListener('dragleave', () => {
        uploadZone.classList.remove('dragover');
    });
    uploadZone.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadZone.classList.remove('dragover');
        imageInput.files = e.dataTransfer.files;
        handleImage();
    });
    
    imageInput.addEventListener('change', handleImage);
    
    function handleImage() {
        const file = imageInput.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = (e) => {
                preview.src = e.target.result;
                preview.style.display = 'block';
                updateTagsDisplay(); // Re-check submit button state
            };
            reader.readAsDataURL(file);
        }
    }
    
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        submitBtn.disabled = true;
        submitBtn.textContent = 'Uploading...';
        
        // Ensure tags are included as comma-separated string
        const formData = new FormData(form);
        formData.set('tags', tagsInput.value);
        
        try {
            const response = await fetch('upload_post.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            
            if (result.success) {
                alert('🎉 Robot design uploaded successfully!');
                window.location.href = 'profile.php';
            } else {
                alert('Upload failed: ' + (result.error || 'Try again'));
                submitBtn.disabled = false;
                submitBtn.textContent = 'Post Design';
            }
        } catch (error) {
            alert('Network error. Try again.');
            submitBtn.disabled = false;
            submitBtn.textContent = 'Post Design';
        }
    });
    </script>
</body>
</html>