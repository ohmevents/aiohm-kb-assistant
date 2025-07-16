=== AIOHM Knowledge Assistant ===
Contributors: ohm-events-agency
Tags: ai, knowledge base, chatbot, assistant, openai, claude, gemini
Requires at least: 5.8
Tested up to: 6.7
Stable tag: 1.2.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Unlock soulful productivity with an AI-powered knowledge assistant for WordPress. Mirror Mode for refining, Muse Mode for sparking ideas.

== Description ==

**AIOHM Knowledge Assistant** transforms your WordPress site into an intelligent, AI-powered knowledge hub that understands your brand's unique voice and serves your audience with precision.

### 🌟 Core Features

**Mirror Mode (Q&A Chatbot)**
* Public-facing chatbot powered by your content
* Answers visitor questions using your knowledge base
* Supports OpenAI, Claude, and Gemini AI models
* Customizable appearance and behavior

**Muse Mode (Brand Assistant)**
* Private AI assistant for content creators
* Understands your brand voice and guidelines
* Helps generate content that sounds authentically you
* Access to Brand Soul questionnaire for voice training

**Intelligent Content Management**
* Automatic content scanning and indexing
* Support for posts, pages, and media files
* Smart content extraction from shortcode-heavy pages
* Vector-based search and retrieval

### 🔧 Technical Features

* **Multi-AI Support**: OpenAI GPT, Claude, and Google Gemini
* **Vector Database**: Advanced semantic search capabilities
* **Membership Integration**: Works with Paid Memberships Pro
* **Content Scanning**: Automatic indexing of posts, pages, and files
* **API Management**: Built-in API key management and usage tracking
* **Brand Voice Training**: AI Brand Core questionnaire system

### 🎯 Perfect For

* **Content Creators** who want AI that speaks their language
* **Businesses** needing intelligent customer support
* **Educators** building knowledge repositories
* **Agencies** managing multiple client voices
* **Membership Sites** with tiered content access

### 🚀 Getting Started

1. Install and activate the plugin
2. Add your AI provider API key (OpenAI, Claude, or Gemini)
3. Scan your content to build your knowledge base
4. Configure your chatbot settings
5. Add chatbots to pages using shortcodes

### 📋 Shortcodes

* `[aiohm_chat]` - Public Q&A chatbot (Mirror Mode)
* `[aiohm_private_assistant]` - Private brand assistant (Muse Mode)
* `[aiohm_search]` - Knowledge base search functionality

### 🔐 Privacy & Security

* Your content stays on your server
* API calls only for AI processing
* No data shared with third parties
* WordPress security standards compliance

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/aiohm-kb-assistant/`
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Navigate to AIOHM Settings to configure your API keys
4. Use the content scanner to build your knowledge base
5. Add chatbots to your pages using the provided shortcodes

== Frequently Asked Questions ==

= What AI providers are supported? =

AIOHM Knowledge Assistant supports OpenAI (GPT-3.5, GPT-4), Anthropic Claude, and Google Gemini. You'll need API keys from your chosen provider(s).

= Does this plugin store my content externally? =

No, your content is stored locally in your WordPress database. Only the text being processed is sent to your chosen AI provider for generating responses.

= Can I use this with membership sites? =

Yes! AIOHM integrates seamlessly with Paid Memberships Pro, allowing you to create different access levels for your AI assistants.

= How does the Brand Soul questionnaire work? =

The Brand Soul questionnaire helps you define your brand's voice, values, and communication style. This information trains your AI to respond in a way that's authentically you.

= Is this suitable for non-technical users? =

Absolutely! AIOHM is designed with a user-friendly interface. While you'll need to obtain API keys from AI providers, the plugin handles all the technical complexity.

= Can I customize the chatbot appearance? =

Yes, the plugin includes customizable CSS and multiple display options to match your site's design.

== Screenshots ==

1. Dashboard overview with Mirror Mode and Muse Mode options
2. Content scanning interface showing posts, pages, and media files
3. Brand Soul questionnaire for voice training
4. Mirror Mode (public chatbot) in action
5. Muse Mode (private assistant) interface
6. Settings panel with API key configuration
7. Knowledge base management screen

== Changelog ==

= 1.2.0 =
* **Enhanced Content Extraction**: Improved handling of shortcode-heavy pages (login, profile, etc.)
* **Better Error Handling**: Detailed error messages and debugging information
* **Visual Progress Indicators**: Real-time progress bars for bulk content processing
* **Status Management**: Clear visual indicators for content status (Ready to Add, Failed, Knowledge Base)
* **API Key Validation**: Pre-processing checks to ensure API keys are configured
* **Cache Management**: Improved cache handling for real-time status updates
* **Fallback Content**: Intelligent content generation for pages with minimal text
* **Debug Console**: Enhanced logging for troubleshooting
* **User Experience**: Improved notifications and feedback messages

= 1.1.11 =
* Fixed AI model selection persistence in settings
* Resolved dashicons display issues in Muse interface
* Enhanced fullscreen mode functionality
* Improved sidebar menu styling
* Fixed delete button positioning issues
* Enhanced mirror mode send button with modern animations
* Corrected settings reflection between modes
* Updated plugin description with brand-focused messaging
* Redesigned WordPress admin menu icon
* Added progress save functionality to Brand Soul questionnaire
* Implemented proper access control for private features
* Applied consistent OHM branding across all pages

= 1.1.0 =
* Initial public release
* Mirror Mode (public Q&A chatbot)
* Muse Mode (private brand assistant)
* Multi-AI provider support (OpenAI, Claude, Gemini)
* Content scanning and indexing
* Brand Soul questionnaire
* Paid Memberships Pro integration

== Upgrade Notice ==

= 1.2.0 =
Major improvements to content processing and user experience. Enhanced error handling and visual feedback. Recommended update for all users.

== Third-Party Services ==

This plugin integrates with the following third-party services:

**AI Providers (Choose One or More):**
* OpenAI API (https://openai.com) - For GPT models
* Anthropic Claude API (https://anthropic.com) - For Claude models  
* Google Gemini API (https://ai.google.dev) - For Gemini models

**Usage:** API calls are made to generate AI responses based on your content and user queries.
**Privacy:** Only the specific text being processed is sent to the AI provider. No personal data or full content is shared.
**Terms:** You must comply with the respective provider's terms of service and privacy policies.

== Support ==

For support, documentation, and updates, visit [aiohm.app](https://aiohm.app) or contact our support team.

== Credits ==

Developed by OHM Events Agency with love for the WordPress community.