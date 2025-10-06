#!/bin/bash

# Script to upload complete project to GitHub
# Run this script to push all changes to GitHub

echo "🚀 Uploading Complete Forum Project to GitHub..."

# Check if git is initialized
if [ ! -d ".git" ]; then
    echo "Initializing git repository..."
    git init
    git remote add origin https://github.com/minazahmad-php/Coding-Master-Forum.git
fi

# Add all files
echo "Adding all files..."
git add .

# Commit changes
echo "Committing changes..."
git commit -m "Complete Enterprise Forum Project - All Features Added

✅ Added Complete Enterprise Features:
- Mobile App (React Native)
- Advanced Security (2FA, Biometric)
- AI Features (Chatbot, Content Moderation)
- Real-time Features (Live Chat, Video Calls)
- Gamification System (Points, Achievements)
- Advanced Analytics & Reporting
- Theme System (Dark Mode, Custom Themes)
- Payment System (Subscriptions, Premium)
- Third-party Integrations
- Performance Optimization
- Complete Testing Suite
- Full Documentation

🚀 Production Ready - All Features Complete!"

# Push to GitHub
echo "Pushing to GitHub..."
git push -u origin main

echo "✅ Upload complete!"
echo "📥 Download from: https://github.com/minazahmad-php/Coding-Master-Forum"
echo "📦 Or download ZIP: https://github.com/minazahmad-php/Coding-Master-Forum/archive/main.zip"