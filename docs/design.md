# Shared visual foundation

The interface uses warm cream, deep olive green and a small gold accent. Display headings use a local serif font; body text uses the system sans-serif. There are no external font requests or new frontend dependencies.

The public homepage, account screens, dashboard, profile and admin user management share the same buttons, fields, cards, icons and brand component. Back Office navigation uses a sidebar on desktop and wraps above the content on small screens. Front Office navigation has a keyboard-accessible mobile toggle.

## Extending the style

- Reuse `x-card`, `x-primary-button`, `x-secondary-button`, `x-button-link`, `x-input-label` and `x-text-input`.
- `x-icon` takes a `name`: leaf, arrow, grid, users, user, logout, lock, check, sun or menu.
- Use `display-title` for page headings, `eyebrow` for small section labels, `text-link` for action links and `role-badge` for role labels.
- Shared colors and component rules live in `resources/css/app.css`.
- Keep meaningful headings, input labels, visible focus states and responsive layouts. Respect reduced-motion preferences.
- Link only to features that exist. The landing-page vision is introductory content, not implemented business functionality.

## Homepage image

Asset: `public/images/olive-grove.jpg`. It is an AI-generated editorial illustration of an olive grove, created using the built-in imagegen tool and encoded as JPEG for the web without changing its content or dimensions. It does not represent a verified photograph of a specific farm.

Final generation prompt:

> Use case: photorealistic-natural. Asset type: OliveTrace website hero photograph. Primary request: a beautiful, quiet Tunisian olive grove in warm late-afternoon light, centuries-old olive trees with sculptural gnarled trunks and silvery green leaves, dry earth, subtle distant hills. Style: premium editorial agricultural photography, natural authentic texture, warm cream and muted olive palette, sunlight filtering through branches, calm and grounded. Composition: portrait 4:5, a strong olive tree in the right half and a winding path through the grove, softly luminous sky in upper quarter, enough textured detail to crop into a website panel. No people, no products, no architecture, no text, no logos, no watermarks. This is the photograph asset only, not a UI mockup.

Only shared presentation changed. No business modules, tables, routes or persistence architecture were added.
