import { createInertiaApp } from "@inertiajs/react"
import { createRoot } from "react-dom/client"
import type { ComponentType } from "react"

const pages = import.meta.glob<{ default: ComponentType }>("./pages/**/*.tsx", { eager: true })

createInertiaApp({
	title: (title) => (title ? `${title} · Suvvi staff` : "Suvvi staff"),
	resolve: (name) => pages[`./pages/${name}.tsx`],
	setup({ el, App, props }) {
		createRoot(el).render(<App {...props} />)
	},
	progress: { color: "#2b66dc" },
})
