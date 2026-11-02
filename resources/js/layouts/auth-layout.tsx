import { Head } from "@inertiajs/react"
import type { ReactNode } from "react"

interface AuthLayoutProps {
	title: string
	description: string
	children: ReactNode
}

/** A narrow centred column for signing in, with the console's name on top. */
export function AuthLayout({ title, description, children }: AuthLayoutProps) {
	return (
		<main className="flex min-h-svh items-center justify-center bg-background px-4 py-10">
			<Head title={title} />
			<div className="flex w-full max-w-sm flex-col gap-6">
				<div className="flex flex-col gap-1">
					<span className="label-caps text-primary">Suvvi staff</span>
					<h1 className="text-xl font-semibold tracking-tight">{title}</h1>
					<p className="text-muted-foreground">{description}</p>
				</div>
				<div className="surface p-6">{children}</div>
			</div>
		</main>
	)
}
