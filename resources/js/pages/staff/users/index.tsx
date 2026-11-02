import { Form, Link } from "@inertiajs/react"
import { CheckIcon, MinusIcon, SearchIcon, UsersIcon } from "lucide-react"

import { EmptyState } from "@/components/empty-state"
import { Pager } from "@/components/pager"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { ConsoleLayout } from "@/layouts/console-layout"
import { formatDate } from "@/lib/format"
import type { Paginated, StaffUser } from "@/types"

interface StaffUsersProps {
	users: Paginated<StaffUser>
	filters: { search?: string }
}

function YesNo({ yes, label }: { yes: boolean; label: string }) {
	return yes ? (
		<CheckIcon className="size-4 text-success" aria-label={`${label}: yes`} />
	) : (
		<MinusIcon className="size-4 text-muted-foreground" aria-label={`${label}: no`} />
	)
}

/** Agents, to look someone up and see the workspaces they're in. */
export default function StaffUsers({ users, filters }: StaffUsersProps) {
	return (
		<ConsoleLayout title="Users" description="Agents with an Insurix account. Look someone up by name or email.">
			<Form action="/staff/users" method="get" className="flex flex-wrap items-end gap-2">
				{({ processing }) => (
					<>
						<div className="flex min-w-60 flex-1 flex-col gap-1.5">
							<Label htmlFor="search">Search</Label>
							<Input
								id="search"
								name="search"
								type="search"
								defaultValue={filters.search ?? ""}
								placeholder="Maria Santos, maria@…"
							/>
						</div>
						<Button type="submit" variant="outline" disabled={processing}>
							<SearchIcon data-icon="inline-start" />
							Search
						</Button>
					</>
				)}
			</Form>

			{users.data.length === 0 ? (
				<EmptyState
					icon={<UsersIcon />}
					title={filters.search ? "No one matches" : "No users yet"}
					description={
						filters.search ? "Try part of their name or email." : "Agents show up here as they sign up."
					}
				/>
			) : (
				<div className="surface overflow-x-auto">
					<Table>
						<TableHeader>
							<TableRow>
								<TableHead>Name</TableHead>
								<TableHead>Workspaces</TableHead>
								<TableHead>Email confirmed</TableHead>
								<TableHead>Two-factor</TableHead>
								<TableHead>Joined</TableHead>
							</TableRow>
						</TableHeader>
						<TableBody>
							{users.data.map((user) => (
								<TableRow key={user.id}>
									<TableCell>
										<span className="font-medium">{user.name}</span>
										<span className="block text-xs text-muted-foreground">{user.email}</span>
									</TableCell>
									<TableCell>
										{user.workspaces.length === 0 ? (
											<span className="text-muted-foreground">None</span>
										) : (
											<ul className="flex flex-col gap-0.5">
												{user.workspaces.map((workspace) => (
													<li key={workspace.id}>
														<Link
															href={`/staff/workspaces/${workspace.id}`}
															className="hover:underline"
														>
															{workspace.name}
														</Link>
														<span className="text-xs text-muted-foreground">
															{" "}
															· {workspace.role.label}
														</span>
													</li>
												))}
											</ul>
										)}
									</TableCell>
									<TableCell>
										<YesNo yes={user.email_verified} label="Email confirmed" />
									</TableCell>
									<TableCell>
										<YesNo yes={user.two_factor} label="Two-factor" />
									</TableCell>
									<TableCell className="text-muted-foreground">
										{formatDate(user.created_at)}
									</TableCell>
								</TableRow>
							))}
						</TableBody>
					</Table>
				</div>
			)}
			<Pager page={users} noun="users" />
		</ConsoleLayout>
	)
}
