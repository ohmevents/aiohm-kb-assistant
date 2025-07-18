## AIOHM WordPress Plugin Documentation

Welcome to the official documentation for the AIOHM WordPress Plugin. This guide is for conscious creators, site administrators, and developers who wish to integrate soulful, voice-aligned AI support into their WordPress websites.

---

### What Is the AIOHM Plugin?

The AIOHM Plugin empowers your website with a voice-aligned AI assistant that reflects your brand's essence. Its standout feature is a dual-mode Knowledge Base system—Public and Private—that no other plugin offers. Public mode powers a customer-facing assistant trained on your approved content. Private mode supports your creative flow with Muse, drawing from confidential documents and training material that remain hidden from public access. Designed for coaches, facilitators, and conscious brands, AIOHM connects your content to a Retrieval-Augmented Generation (RAG) engine, creating an assistant deeply aware of your unique knowledge structure.

---

### Core Features Overview

* Knowledge Base Engine: Index and retrieve content using full-text and vector search.
* Voice-Aligned Chat Assistant: Embed public or private chatbots using shortcodes.
* Mirror Mode: Train your assistant to reflect your unique tone and expression style.
* Muse Mode: Unlock soulful writing prompts, brand-aligned content generation, and creative co-writing support.
* Custom Embeddings with OpenAI/Gemini: Supports private LLM usage and custom prompts.
* Site & File Crawling: Index pages, posts, and supported uploads (PDF, CSV, TXT, JSON).
* Membership Integration: Restrict private assistant features to paid members (via PMPro).

---

### Includes Folder Breakdown

**core-init.php**
Initializes the plugin, sets up hooks for AJAX handling, API key validation, and data import/export functions.

**rag-engine.php**
Implements the RAG engine. Handles content chunking, embedding generation, and context retrieval using full-text and vector similarity.

**crawler-site.php**
Crawls and indexes posts and pages from your WordPress site into the knowledge base.

**crawler-uploads.php**
Indexes supported files from the Media Library (PDF, JSON, CSV, TXT) into your AI’s memory.

**ai-gpt-client.php**
Handles interaction with OpenAI or Gemini models. Supports embedding and chat completion calls.

**user-functions.php**
Manages user data like ARMember IDs, access levels, and stores chat interactions.

**aiohm-kb-manager.php**
Creates the admin UI for managing knowledge base entries via a custom WP\_List\_Table.

**api-client-app.php**
Communicates with the aiohm.app site to fetch membership data based on user email.

**chat-box.php**
Renders the frontend chat interface — including message bubbles, quick replies, and suggested prompts.

**pmpro-integration.php**
Integrates with Paid Memberships Pro. Manages access controls based on membership level.

**settings-page.php**
Creates the admin dashboard UI and settings pages. Enqueues backend scripts and styles.

**shortcode-chat.php**
Implements `[aiohm_chat]` shortcode. Renders public chatbot interface for site visitors.

**shortcode-search.php**
Implements `[aiohm_search]` shortcode. Provides search access to the public knowledge base.

**shortcode-private-assistant.php**
Implements `[aiohm_private_assistant]` shortcode. Renders private assistant for logged-in members.

**frontend-widget.php**
Enqueues necessary frontend assets based on active shortcodes.

---

### Templates Folder

Customizable frontend templates for chat, search, and assistant interfaces. Modify these to match your site's aesthetic and tone.

---

### Privacy & Personalization (Private Mode)

The plugin supports private VPS and LLM hosting, encrypted file input, and user-specific AI fine-tuning for secure, sovereign experiences.

---

### Using Shortcodes

Embed intelligent features easily with:

* `[aiohm_chat]` – Public chat based on knowledge base
* `[aiohm_search]` – Search box for public KB
* `[aiohm_private_assistant]` – Private AI assistant (for members only)

---

### Installation & Setup

1. Upload and activate the plugin via WordPress admin.
2. Set your OpenAI or Gemini API key under the Settings menu.
3. Crawl site content and/or upload documents.
4. Use shortcodes to embed assistants where needed.

---

### Need Help?

This plugin was designed with care and consciousness. If something feels unclear or off-tone, reach out — human support is always available.

---

### Final Note

Your voice isn’t a task to automate. It’s a presence to be supported. Let AIOHM be the assistant that holds your frequency with care.
