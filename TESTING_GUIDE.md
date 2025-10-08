# 🧪 Testing Guide - Audio Translation App

## Quick Test Checklist

Use this guide to verify all functionality works after deployment.

## ✅ **1. File Upload & Selection**

### Audio Translation Page (`/audio/create`)

**Test File Selection:**
1. Navigate to Audio Translation page
2. Click anywhere in the blue dashed dropzone
3. ✅ File picker should open
4. Select an audio file (MP3, WAV, M4A)
5. ✅ File name and size should appear with green checkmark
6. ✅ Dropzone content should be hidden
7. ✅ File info should show

**Test Drag & Drop:**
1. Drag an audio file over the dropzone
2. ✅ Border should change color (hover effect)
3. Drop the file
4. ✅ File should be accepted
5. ✅ UI should update with file info

**Test Invalid File:**
1. Try to select a .txt or .jpg file
2. ✅ Should show error: "Only audio files are allowed..."

**Test Large File:**
1. Try to select file > 100MB
2. ✅ Should show error: "File is too large..."

---

## ✅ **2. Form Functionality**

### Language Selection
1. Click "Source Language" dropdown
2. ✅ Should see organized groups (Major, European, Asian, etc.)
3. Select "English"
4. Click "Target Language" dropdown
5. Select "Spanish"
6. ✅ Both should save selection

### Voice Selection
1. Click "Voice Selection" dropdown
2. ✅ Should see 30+ voices organized by gender
3. Select a voice (e.g., "Aoede")
4. ✅ Selection should save

### Style Instruction (Optional)
1. Type in style instruction box
2. ✅ Text should appear
3. ✅ Up to 5000 characters allowed

---

## ✅ **3. Upload & Processing**

### Submit Form
1. Fill all required fields:
   - Upload file ✅
   - Source language ✅
   - Target language ✅
   - Voice ✅
2. Click "Upload & Translate"
3. ✅ Button should change to "Uploading..." with spinner
4. ✅ Upload progress bar should appear (blue)
5. ✅ Should redirect to details page

### Details Page Progress
1. On details page `/audio/{id}`
2. ✅ Progress bar should update in real-time
3. ✅ Status should show each step:
   - Audio Uploaded ✅
   - Transcribing Audio... ⏳
   - Translating Text... ⏳
   - Generating Audio... ⏳
   - Completed ✅
4. ✅ Page should auto-refresh when done

---

## ✅ **4. Text-to-Audio**

### Create Text-to-Audio (`/text-to-audio/create`)

**Test Character Counter:**
1. Start typing in textarea
2. ✅ Character count should update: "X / 50000"
3. Type > 900 characters
4. ✅ Yellow warning should appear: "Text will be split into chunks"
5. ✅ Border should turn red if > 50000

**Test Submit:**
1. Fill text (min 10 chars)
2. Select language
3. Select voice
4. Click "Generate Audio"
5. ✅ Should redirect to details page
6. ✅ Processing should start

---

## ✅ **5. Dashboard Features**

### Stats Cards (`/audio/index`)
1. Check stats display:
   - ✅ Total files
   - ✅ Translations left
   - ✅ Completed count
   - ✅ Processing count
   - ✅ Failed count

### File Cards
1. ✅ Each file shows status badge (color-coded)
2. ✅ Processing files show animated pulse
3. ✅ "View" button works
4. ✅ "Download" button (if completed)
5. ✅ "Delete" button with confirmation

---

## ✅ **6. Real-Time Updates**

### AJAX Polling
1. Upload a file
2. Go to details page
3. Open browser DevTools (F12) → Console
4. ✅ Should see: "Status update: {status: 'transcribing', ...}"
5. ✅ Updates every 2 seconds
6. ✅ Progress bar animates smoothly
7. ✅ Page refreshes automatically when complete

---

## ✅ **7. Download & Delete**

### Download
1. Go to completed translation
2. Click "Download Translated Audio"
3. ✅ MP3 file should download
4. ✅ File should play correctly

### Delete
1. Click "Delete" button
2. ✅ Confirmation dialog appears
3. Click "OK"
4. ✅ Redirects to index
5. ✅ Success message shows
6. ✅ File removed from list

---

## ✅ **8. Authorization & Credits**

### Free Tier
1. Create new account
2. ✅ Should have 2 free translations
3. Use both free translations
4. Try to create 3rd translation
5. ✅ Should get error: "You have no more translations available"

### Credits Purchase
1. Go to Credits page
2. Click "Buy Credits"
3. ✅ Redirects to Stripe checkout
4. Complete test payment (use test card: 4242 4242 4242 4242)
5. ✅ Redirects back with success
6. ✅ Credits added to account

---

## ✅ **9. Mobile Responsiveness**

### Mobile Menu
1. Resize browser to mobile size (<768px)
2. ✅ Hamburger menu appears
3. Click hamburger
4. ✅ Menu slides open
5. ✅ All links visible
6. Click outside menu
7. ✅ Menu closes

### Mobile Upload
1. On mobile device
2. ✅ Dropzone is touch-friendly
3. ✅ File picker works on tap
4. ✅ Form is easy to fill
5. ✅ Buttons are large enough (44px min)

---

## ✅ **10. Error Handling**

### Test Failed Upload
1. Upload invalid file format
2. ✅ Error message shows in red
3. ✅ Form doesn't submit

### Test Network Error
1. Disconnect internet
2. Try to submit form
3. ✅ Appropriate error handling

### Test Processing Failure
1. Check details page of failed job
2. ✅ Red "Failed" badge
3. ✅ Error message displayed
4. ✅ Delete option available

---

## 🐛 **Common Issues & Fixes**

### Issue: "Select File" button doesn't work
**Fix:** ✅ FIXED - Added uploadProgress element and improved JS

### Issue: Character counter not updating
**Check:** Console for errors (F12)
**Fix:** Ensure `text_content` textarea has correct ID

### Issue: Progress not updating
**Check:** Browser console for AJAX errors
**Fix:** Ensure queue worker is running

### Issue: Upload shows "413 Payload Too Large"
**Fix:** Check php.ini and nginx config for upload limits

---

## 📝 **Browser Console Commands**

Test JavaScript manually in browser console (F12):

```javascript
// Test file input
document.getElementById('audio').click();

// Check if elements exist
console.log('dropZone:', document.getElementById('dropZone'));
console.log('audioInput:', document.getElementById('audio'));

// Test character counter
document.getElementById('text_content').value = 'Test';
document.getElementById('text_content').dispatchEvent(new Event('input'));
```

---

## 🚀 **Performance Tests**

### Upload Speed
- Small file (5MB): Should upload in < 2 seconds
- Medium file (25MB): Should upload in < 10 seconds
- Large file (100MB): Should upload in < 30 seconds

### Processing Speed
- Transcription: ~30-60 seconds for 5min audio
- Translation: ~5-10 seconds
- TTS Generation: ~10-30 seconds depending on text length
- **Total:** ~1-2 minutes for complete flow

---

## ✅ **Admin Panel Tests**

### Admin Login (`/admin/login`)
1. Login as admin user
2. ✅ Redirects to admin dashboard

### Dashboard
1. ✅ Revenue statistics show
2. ✅ User count correct
3. ✅ Charts render
4. ✅ Recent payments table

### Credit Management
1. Go to Users page
2. Click on a user
3. Add credits
4. ✅ Balance updates
5. ✅ Transaction created
6. Remove credits
7. ✅ Balance decreases

---

## 🎯 **Success Criteria**

All features should work:
- [x] File upload (click & drag-drop)
- [x] Form validation
- [x] Real-time progress
- [x] Audio generation
- [x] Download functionality
- [x] Delete functionality
- [x] Mobile responsiveness
- [x] Admin panel
- [x] Payment system
- [x] Error handling

---

## 📞 **If Issues Persist**

1. **Clear browser cache:** Ctrl+Shift+Delete
2. **Hard reload:** Ctrl+Shift+R
3. **Check console:** F12 → Console tab
4. **Check network:** F12 → Network tab
5. **Check logs:** `storage/logs/laravel.log`

## Debugging Commands

```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Restart queue
php artisan queue:restart

# Watch logs
tail -f storage/logs/laravel.log
```

---

**All Fixed and Ready to Test!** ✅🎉

Report any issues you find and I'll fix them immediately!
