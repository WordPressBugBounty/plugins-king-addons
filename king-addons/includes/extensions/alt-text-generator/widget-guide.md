# AI Alt Text Generator

## Overview

The AI Alt Text Generator module for King Addons pr2. **Auto generation not working**
   - Enable "Auto Generate Alt Text" in settings
   - Check WP Cron is functioning (`wp cron event list` via WP-CLI)
   - Verify API key and model settings
   - Check queue status (see debug section below)

3. **Batch uploads failing**
   - Queue system should handle this automatically
   - Check debug.log for error messages
   - Monitor queue status via debug page

4. **API rate limits**
   - System automatically limits to 1 request per minute
   - Failed requests are retried up to 3 times
   - Check OpenAI usage dashboard for rate limit issues

5. **API errors**
   - Check OpenAI account balance ($5 minimum required)
   - Verify API key is valid
   - Ensure model supports vision (gpt-4o, gpt-4-vision-preview)

### Debug Mode

To enable debug monitoring:
1. Include `debug-functions.php` in your theme's functions.php
2. Go to King Addons > Alt Text Debug
3. Monitor queue status and process manually if needed

#### Debug Information
- Queue status (pending, processing, next run)
- Individual image retry counts
- Manual queue processing
- Queue clearing optionsmatic and manual alt text generation for images using OpenAI's vision capabilities.

## Features

- **Manual Generation**: Add "Generate" buttons in Media Library for on-demand alt text creation
- **Auto Generation**: Automatically generate alt text when new images are uploaded
- **Media Library Integration**: Custom column showing alt text status
- **Modal Integration**: Buttons in media editing modals
- **Settings Control**: Toggle features individually in AI Settings

## Settings

Navigate to **King Addons > AI Settings** to configure:

### AI Alt Text Button
- **Enable/Disable**: Show "Generate" button in Media Library
- **Location**: Media Library list view and modal windows
- **Function**: Manual alt text generation for existing images

### Auto Generate Alt Text
- **Enable/Disable**: Automatically generate alt text for new uploads
- **Method**: Queue-based processing with retry logic
- **Timing**: Processed every minute via WP Cron
- **Reliability**: 3 retry attempts per image, rate limiting to respect API limits

## Requirements

- OpenAI API key configured in King Addons AI Settings
- At least one alt text feature enabled
- Vision-capable OpenAI model (gpt-4o, gpt-4-vision-preview, etc.)

## File Structure

```
includes/extensions/alt-text-generator/
├── Alt_Text_Generator.php     // Main PHP class
├── alt-text-media.js         // JavaScript for UI interactions
└── alt-text-styles.css       // Styling for buttons and UI
```

## Usage

### Manual Generation
1. Go to Media Library
2. Find images without alt text
3. Click "Generate" button in the "AI Alt Text" column
4. Alt text will be generated and saved automatically

### Auto Generation
1. Enable "Auto Generate Alt Text" in AI Settings
2. Upload new images to Media Library (single or batch)
3. Images are added to processing queue
4. Queue is processed every minute automatically
5. Failed generations are retried up to 3 times

## Queue System

### How It Works
- **Queue-based Processing**: Images are added to a queue instead of immediate processing
- **Rate Limiting**: Only 1 image processed per minute to respect API limits
- **Retry Logic**: Failed generations are retried up to 3 times
- **Batch Upload Support**: Handles multiple simultaneous uploads reliably

### Monitoring
- Queue status can be monitored via debug page (if enabled)
- Processing logs are written to WordPress debug.log
- Automatic cleanup on plugin deactivation

## API Integration

- **Endpoint**: OpenAI Chat Completions API
- **Model**: Uses the text model from AI Settings (gpt-4o recommended)
- **Image Format**: Base64 encoded images sent as data URIs
- **Detail Level**: Low (for faster processing)
- **Max Tokens**: 50 (for concise alt text)
- **Character Limit**: 125 characters maximum

## Troubleshooting

### Common Issues

1. **No buttons showing**
   - Check if "AI Alt Text Button" is enabled in settings
   - Verify OpenAI API key is set

2. **Auto generation not working**
   - Enable "Auto Generate Alt Text" in settings
   - Check WP Cron is functioning
   - Verify API key and model settings

3. **API errors**
   - Check OpenAI account balance ($5 minimum required)
   - Verify API key is valid
   - Ensure model supports vision (gpt-4o, gpt-4-vision-preview)

### Debug Information

Check browser console for JavaScript errors and WordPress debug logs for PHP errors.

## Changelog

### v1.0.0
- Initial implementation
- Manual generation buttons
- Auto generation for new uploads
- Settings integration
- Media Library column
- Modal integration
