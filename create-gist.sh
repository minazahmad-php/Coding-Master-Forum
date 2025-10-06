#!/bin/bash

# Create GitHub Gist for file download
# This script will create a gist with the ZIP file

echo "Creating GitHub Gist for file download..."

# Create a base64 encoded version of the ZIP file
echo "Encoding ZIP file to base64..."
base64 -w 0 forum-project-final.zip > forum-project-final-base64.txt

# Create a download script
cat > download-script.sh << 'EOF'
#!/bin/bash

# Download and decode the forum project
echo "Downloading Complete Forum Project..."

# Download the base64 encoded file
curl -s "https://gist.githubusercontent.com/YOUR_USERNAME/YOUR_GIST_ID/raw/forum-project-final-base64.txt" -o forum-project-final-base64.txt

# Decode the base64 file back to ZIP
base64 -d forum-project-final-base64.txt > forum-project-final.zip

# Clean up
rm forum-project-final-base64.txt

echo "✅ Download complete! forum-project-final.zip is ready."
echo "📦 File size: $(ls -lh forum-project-final.zip | awk '{print $5}')"
echo "🚀 Extract the ZIP file to get the complete project."
EOF

chmod +x download-script.sh

echo "✅ Gist files created!"
echo "📝 Next steps:"
echo "1. Upload forum-project-final-base64.txt to GitHub Gist"
echo "2. Upload download-script.sh to GitHub Gist"
echo "3. Share the gist links for download"