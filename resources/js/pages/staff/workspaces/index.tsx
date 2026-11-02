import { Form, Link } from "@inertiajs/react"
import { BuildingIcon, SearchIcon } from "lucide-react"

import { EmptyState } from "@/components/empty-state"
import { Pager } from "@/components/pager"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { ConsoleLayout } from "@/layouts/console-layout"
import { formatCount, formatDate, planText } from "@/lib/format"
import { cn } from "@/lib/utils"
import type { Paginated, StaffWorkspace } from "@/types"

interface StaffWorkspacesProps {
	workspaces: Paginated<StaffWorkspace>
	filters: { search?: string; plan?: string; deleted?: string | boolean }
}

const planItems = [
	{ value: null, label: "Any plan" },
	{ value: "premium", label: "Premium" },
	{ value: "free", label: "Free" },
]

/** Every workspace, to find one by its name or a member's name or email. */
export default function StaffWorkspaces({ workspaces, filters }: StaffWorkspacesProps) {
	const deleted = filters.deleted === true || filters.deleted === "1"
	const tabs = [
		{ label: "Active", href: "/staff/workspaces", current: !deleted },
		{ label: "Deleted", href: "/staff/workspaces?deleted=1", current: deleted },
	]

	return (
		<ConsoleLayout title="Workspaces" description="Find a workspace by its name, or by a member’s name or email.">
			<div className="flex flex-col gap-3">
				<nav aria-label="Workspace status" className="flex gap-1">
					{tabs.map((tab) => (
						<Button
							key={tab.label}
							variant={tab.current ? "secondary" : "ghost"}
							size="sm"
							nativeButton={false}
							render={<Link href={tab.href} aria-current={tab.current ? "page" : undefined} />}
						>
							{tab.label}
						</Button>
					))}
				</nav>
				<Form action="/staff/workspaces" method="get" className="flex flex-wrap items-end gap-2">
					{({ processing }) => (
						<>
							{deleted && <input type="hidden" name="deleted" value="1" />}
							<div className="flex min-w-60 flex-1 flex-col gap-1.5">
								<Label htmlFor="search">Search</Label>
								<Input
									id="search"
									name="search"
									type="search"
									defaultValue={filters.search ?? ""}
									placeholder="Santos Insurance, maria@…"
								/>
							</div>
							<div className="flex w-40 flex-col gap-1.5">
								<Label htmlFor="plan">Plan</Label>
								<Select name="plan" items={planItems} defaultValue={filters.plan ?? null}>
									<SelectTrigger id="plan" className="w-full">
										<SelectValue />
									</SelectTrigger>
									<SelectContent>
										<SelectGroup>
											{planItems.map((item) => (
												<SelectItem key={item.label} value={item.value}>
													{item.label}
												</SelectItem>
											))}
										</SelectGroup>
									</SelectContent>
								</Select>
							</div>
							<Button type="submit" variant="outline" disabled={processing}>
								<SearchIcon data-icon="inline-start" />
								Search
							</Button>
						</>
					)}
				</Form>
			</div>

			{workspaces.data.length === 0 ? (
				<EmptyState
					icon={<BuildingIcon />}
					title={
						filters.search || filters.plan
							? "No workspaces match"
							: deleted
								? "No deleted workspaces"
								: "No workspaces yet"
					}
					description={
						filters.search || filters.plan
							? "Try another name or email, or clear the plan."
							: "They show up here as agents sign up."
					}
				/>
			) : (
				<div className="surface overflow-x-auto">
					<Table>
						<TableHeader>
							<TableRow>
								<TableHead>Workspace</TableHead>
								<TableHead>Plan</TableHead>
								<TableHead className="text-right">Members</TableHead>
								<TableHead className="text-right">Leads</TableHead>
								<TableHead className="text-right">Clients</TableHead>
								<TableHead>{deleted ? "Deleted" : "Created"}</TableHead>
							</TableRow>
						</TableHeader>
						<TableBody>
							{workspaces.data.map((workspace) => (
								<TableRow key={workspace.id}>
									<TableCell>
										<Link
											href={`/staff/workspaces/${workspace.id}`}
											prefetch
											className="font-medium hover:underline"
										>
											{workspace.name}
										</Link>
										{workspace.owner && (
											<span className="block text-xs text-muted-foreground">
												{workspace.owner.name} · {workspace.owner.email}
											</span>
										)}
									</TableCell>
									<TableCell>
										<Badge variant={workspace.plan.value === "premium" ? "primary" : "outline"}>
											{planText(workspace.plan, workspace.plan_ends_at)}
										</Badge>
									</TableCell>
									<TableCell className="text-right tabular-nums">
										{formatCount(workspace.members_count)}
									</TableCell>
									<TableCell className="text-right tabular-nums">
										{formatCount(workspace.leads_count)}
									</TableCell>
									<TableCell className="text-right tabular-nums">
										{formatCount(workspace.clients_count)}
									</TableCell>
									<TableCell className={cn("text-muted-foreground", deleted && "text-foreground")}>
										{deleted && workspace.deleted_at ? (
											<>
												{formatDate(workspace.deleted_at)}
												{workspace.purged_at && (
													<span className="block text-xs text-muted-foreground">
														Gone for good {formatDate(workspace.purged_at)}
													</span>
												)}
											</>
										) : (
											formatDate(workspace.created_at)
										)}
									</TableCell>
								</TableRow>
							))}
						</TableBody>
					</Table>
				</div>
			)}
			<Pager page={workspaces} noun="workspaces" />
		</ConsoleLayout>
	)
}
