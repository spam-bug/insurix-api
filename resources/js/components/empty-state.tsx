import type { ReactNode } from "react"

import { Empty, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from "@/components/ui/empty"

interface EmptyStateProps {
	icon: ReactNode
	title: string
	description: string
}

export function EmptyState({ icon, title, description }: EmptyStateProps) {
	return (
		<Empty className="surface">
			<EmptyHeader>
				<EmptyMedia variant="icon">{icon}</EmptyMedia>
				<EmptyTitle>{title}</EmptyTitle>
				<EmptyDescription>{description}</EmptyDescription>
			</EmptyHeader>
		</Empty>
	)
}
