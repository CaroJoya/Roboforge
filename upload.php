<?php
require_once 'session.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Robot Design - RoboForge</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0a0a0a 0%, #1a1a2e 100%);
            color: #e0e0e0;
            min-height: 100vh;
        }
        .container {
            max-width: 700px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .upload-card {
            background: rgba(26, 26, 46, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 30px;
            overflow: hidden;
            border: 1px solid rgba(0, 198, 251, 0.3);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }
        .header {
            padding: 2rem;
            text-align: center;
            border-bottom: 1px solid rgba(0, 198, 251, 0.2);
        }
        .header h1 {
            font-size: 2rem;
            background: linear-gradient(135deg, #fff, #00c6fb);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 0.5rem;
        }
        .upload-zone {
            margin: 2rem;
            padding: 3rem;
            border: 2px dashed rgba(0, 198, 251, 0.3);
            border-radius: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: rgba(10, 10, 10, 0.5);
        }
        .upload-zone:hover, .upload-zone.dragover {
            border-color: #00c6fb;
            background: rgba(0, 198, 251, 0.1);
        }
        .upload-icon {
            font-size: 4rem;
            color: #00c6fb;
            margin-bottom: 1rem;
        }
        .preview-container {
            text-align: center;
            margin: 2rem;
            display: none;
        }
        #preview {
            max-width: 100%;
            max-height: 300px;
            border-radius: 15px;
            border: 2px solid #00c6fb;
        }
        .form-group {
            padding: 0 2rem 2rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #00c6fb;
        }
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 1rem;
            background: rgba(10, 10, 10, 0.8);
            border: 1px solid rgba(0, 198, 251, 0.3);
            border-radius: 12px;
            color: white;
            font-family: 'Inter', sans-serif;
            font-size: 1rem;
            transition: all 0.3s;
        }
        .form-group input:focus, .form-group textarea:focus {
            outline: none;
            border-color: #00c6fb;
            box-shadow: 0 0 10px rgba(0, 198, 251, 0.2);
        }
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        .tags-input {
            background: rgba(10, 10, 10, 0.8) url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="%2300c6fb" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>') 1rem center no-repeat;
            background-size: 18px;
            padding-left: 3rem;
        }
        .upload-btn {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #00c6fb, #005bea);
            border: none;
            border-radius: 40px;
            color: white;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 1rem;
        }
        .upload-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 198, 251, 0.3);
        }
        .upload-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container">
        <div class="upload-card">
            <div class="header">
                <h1><i class="fas fa-robot"></i> Share Your Creation</h1>
                <p style="color: #888;">Upload your robot design and inspire the community</p>
            </div>
            
            <form id="uploadForm" enctype="multipart/form-data">
                <div class="upload-zone" onclick="document.getElementById('imageInput').click()">
                    <div class="upload-icon">
                        <i class="fas fa-cloud-upload-alt"></i>
                    </div>
                    <div class="upload-text">Click or drag & drop to upload</div>
                    <div style="color: #666; font-size: 0.85rem; margin-top: 0.5rem;">JPG, PNG up to 50MB</div>
                    <input type="file" id="imageInput" name="image" accept="image/jpeg,image/png" style="display: none;" required>
                </div>
                
                <div class="preview-container" id="previewContainer">
                    <img id="preview" src="" alt="Preview">
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-pencil-alt"></i> Caption</label>
                    <textarea name="caption" placeholder="Describe your robot design, materials used, and how it works..." maxlength="500"></textarea>
                    
                    <label style="margin-top: 1.5rem;"><i class="fas fa-tags"></i> Tags (optional)</label>
                    <input type="text" name="tags" class="tags-input" placeholder="#arduino #robotics #3dprint #raspberrypi" maxlength="200">
                    
                    <button type="submit" class="upload-btn" id="submitBtn" disabled>
                        <i class="fas fa-upload"></i> Post Design
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const form = document.getElementById('uploadForm');
        const imageInput = document.getElementById('imageInput');
        const preview = document.getElementById('preview');
        const previewContainer = document.getElementById('previewContainer');
        const submitBtn = document.getElementById('submitBtn');
        const uploadZone = document.querySelector('.upload-zone');

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
                    previewContainer.style.display = 'block';
                    uploadZone.style.display = 'none';
                    submitBtn.disabled = false;
                };
                reader.readAsDataURL(file);
            }
        }

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';

            const formData = new FormData(form);
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
                    submitBtn.innerHTML = '<i class="fas fa-upload"></i> Post Design';
                }
            } catch (error) {
                alert('Network error. Try again.');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-upload"></i> Post Design';
            }
        });
    </script>
</body>
</html>