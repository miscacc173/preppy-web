import { useHead } from "@unhead/vue";

/**
 * Shorthand for the per-route `head: () => ({ meta: [...] })` blocks in the
 * source's TanStack Start routes. Call at the top of a route view's <script setup>:
 *
 *   setPageMeta({ title: "Pricing — Preppy", description: "..." });
 *
 * Wraps @unhead/vue's useHead() (the Vue equivalent of TanStack Start's head
 * management) and mirrors title/description into og:/twitter: tags the same
 * way __root.tsx's home-page meta block did, so social previews keep working.
 */
export function setPageMeta(opts: { title: string; description?: string; image?: string }) {
  useHead({
    title: opts.title,
    meta: [
      opts.description ? { name: "description", content: opts.description } : undefined,
      { property: "og:title", content: opts.title },
      opts.description ? { property: "og:description", content: opts.description } : undefined,
      { name: "twitter:title", content: opts.title },
      opts.description ? { name: "twitter:description", content: opts.description } : undefined,
      opts.image ? { property: "og:image", content: opts.image } : undefined,
      opts.image ? { name: "twitter:image", content: opts.image } : undefined,
    ].filter(Boolean) as unknown as Record<string, string>[],
  });
}
