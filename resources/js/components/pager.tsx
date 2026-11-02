import { Link } from "@inertiajs/react"
import { ChevronLeftIcon, ChevronRightIcon } from "lucide-react"

import { Button } from "@/components/ui/button"
import { formatCount } from "@/lib/format"
import type { Paginated } from "@/types"

interface PagerProps {
	page: Paginated<unknown>
	/** What's counted: "workspaces", "users". */
	noun: string
}

/** "Showing 1–25 of 140 workspaces" with Previous and Next, as Inertia visits. */
export function Pager({ page, noun }: PagerProps) {
	const { meta, links } = page
	if (meta.total === 0) return null

	return (
		<nav aria-label="Pages" className="flex items-center justify-between gap-3 text-sm text-muted-foreground">
			<span>
				Showing {meta.from}–{meta.to} of {formatCount(meta.total)} {noun}
			</span>
			{meta.last_page > 1 && (
				<div className="flex gap-1">
					<Button
						variant="outline"
						size="sm"
						disabled={!links.prev}
						nativeButton={false}
						render={links.prev ? <Link href={links.prev} preserveScroll /> : <span />}
					>
						<ChevronLeftIcon data-icon="inline-start" />
						Previous
					</Button>
					<Button
						variant="outline"
						size="sm"
						disabled={!links.next}
						nativeButton={false}
						render={links.next ? <Link href={links.next} preserveScroll /> : <span />}
					>
						Next
						<ChevronRightIcon data-icon="inline-end" />
					</Button>
				</div>
			)}
		</nav>
	)
}
