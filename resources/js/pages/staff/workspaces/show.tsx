import { Form, Link, router } from "@inertiajs/react"
import { useState } from "react"
import { ArrowLeftIcon, RotateCcwIcon, SparklesIcon, TriangleAlertIcon } from "lucide-react"

import { errorProps, FieldErrors } from "@/components/field-errors"
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert"
import {
	AlertDialog,
	AlertDialogCancel,
	AlertDialogContent,
	AlertDialogDescription,
	AlertDialogFooter,
	AlertDialogHeader,
	AlertDialogTitle,
} from "@/components/ui/alert-dialog"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Field, FieldDescription, FieldLabel } from "@/components/ui/field"
import { Input } from "@/components/ui/input"
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Spinner } from "@/components/ui/spinner"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { ConsoleLayout } from "@/layouts/console-layout"
import { formatBytes, formatCount, formatDate, formatDateTime, planText } from "@/lib/format"
import type { StaffWorkspaceDetail, WorkspaceUsage } from "@/types"

interface StaffWorkspaceProps {
	workspace: StaffWorkspaceDetail
	usage: WorkspaceUsage | null
	/** Admins change things; support only looks. */
	can_change: boolean
}

const lengths = [
	{ value: "30", label: "30 days" },
	{ value: "90", label: "90 days" },
	{ value: "180", label: "6 months" },
	{ value: "365", label: "1 year" },
	{ value: "forever", label: "No end date" },
]

/** One workspace: who's in it, how much it uses, its Premium and what staff changed on it. */
export default function StaffWorkspace({ workspace, usage, can_change }: StaffWorkspaceProps) {
	const premium = workspace.plan.value === "premium"

	return (
		<ConsoleLayout
			title={workspace.name}
			description={`Created ${formatDate(workspace.created_at)}`}
			actions={
				<Button variant="outline" size="sm" nativeButton={false} render={<Link href="/staff/workspaces" />}>
					<ArrowLeftIcon data-icon="inline-start" />
					All workspaces
				</Button>
			}
		>
			{workspace.deleted_at && (
				<Alert variant="destructive">
					<TriangleAlertIcon />
					<AlertTitle>Deleted on {formatDate(workspace.deleted_at)}</AlertTitle>
					<AlertDescription className="flex flex-wrap items-center justify-between gap-3">
						<span>
							It’s gone for good on{" "}
							{workspace.purged_at ? formatDate(workspace.purged_at) : "its purge date"}, unless it’s
							restored before then.
						</span>
						{can_change && (
							<Form
								action={`/staff/workspaces/${workspace.id}/restoration`}
								method="post"
								disableWhileProcessing
							>
								{({ processing }) => (
									<Button type="submit" variant="outline" size="sm" disabled={processing}>
										{processing ? (
											<Spinner data-icon="inline-start" />
										) : (
											<RotateCcwIcon data-icon="inline-start" />
										)}
										Restore workspace
									</Button>
								)}
							</Form>
						)}
					</AlertDescription>
				</Alert>
			)}

			<div className="grid gap-4 md:grid-cols-3">
				<Card size="sm">
					<CardHeader>
						<CardDescription>Owner</CardDescription>
						<CardTitle>{workspace.owner?.name ?? "No owner"}</CardTitle>
					</CardHeader>
					<CardContent className="text-muted-foreground">{workspace.owner?.email}</CardContent>
				</Card>
				<Card size="sm">
					<CardHeader>
						<CardDescription>In the workspace</CardDescription>
						<CardTitle className="tabular-nums">
							{formatCount(workspace.leads_count)} leads · {formatCount(workspace.clients_count)} clients
						</CardTitle>
					</CardHeader>
					<CardContent className="text-muted-foreground">
						{workspace.members_count} {workspace.members_count === 1 ? "member" : "members"}
					</CardContent>
				</Card>
				<Card size="sm">
					<CardHeader>
						<CardDescription>Usage</CardDescription>
						<CardTitle className="tabular-nums">
							{usage
								? `${formatBytes(usage.storage.used)} of ${formatBytes(usage.storage.limit)}`
								: "Not measured"}
						</CardTitle>
					</CardHeader>
					{usage && (
						<CardContent className="text-muted-foreground tabular-nums">
							{usage.seats.used} of {usage.seats.limit} seats · {usage.forms.used}
							{usage.forms.limit === null ? "" : ` of ${usage.forms.limit}`} forms
						</CardContent>
					)}
				</Card>
			</div>

			<Card>
				<CardHeader>
					<CardTitle className="flex items-center gap-2">
						Plan
						<Badge variant={premium ? "primary" : "outline"}>
							{planText(workspace.plan, workspace.plan_ends_at)}
						</Badge>
					</CardTitle>
					<CardDescription>
						{can_change
							? "Days add on to Premium that’s still running. Every change is in the log below."
							: "Only admins can change the plan."}
					</CardDescription>
				</CardHeader>
				{can_change && !workspace.deleted_at && (
					<CardContent className="flex flex-col gap-4">
						<GivePremiumForm workspaceId={workspace.id} />
						{premium && <EndPremiumButton workspaceId={workspace.id} name={workspace.name} />}
					</CardContent>
				)}
			</Card>

			<section aria-labelledby="members" className="flex flex-col gap-3">
				<h2 id="members" className="font-semibold">
					Members
				</h2>
				<div className="surface overflow-x-auto">
					<Table>
						<TableHeader>
							<TableRow>
								<TableHead>Name</TableHead>
								<TableHead>Email</TableHead>
								<TableHead>Role</TableHead>
							</TableRow>
						</TableHeader>
						<TableBody>
							{workspace.members.map((member) => (
								<TableRow key={member.id}>
									<TableCell className="font-medium">{member.name}</TableCell>
									<TableCell className="text-muted-foreground">{member.email}</TableCell>
									<TableCell>
										<Badge variant="outline">{member.role.label}</Badge>
									</TableCell>
								</TableRow>
							))}
						</TableBody>
					</Table>
				</div>
			</section>

			<div className="grid gap-6 md:grid-cols-2">
				<section aria-labelledby="premium-history" className="flex flex-col gap-3">
					<h2 id="premium-history" className="font-semibold">
						Premium history
					</h2>
					{workspace.grants.length === 0 ? (
						<p className="text-muted-foreground">Never had Premium.</p>
					) : (
						<ul className="flex flex-col divide-y surface">
							{workspace.grants.map((grant) => (
								<li key={grant.id} className="flex flex-col gap-0.5 px-4 py-3">
									<span className="font-medium">
										{grant.days ? `${grant.days} days` : "No end date"}
										{grant.plan_ends_at && (
											<span className="text-muted-foreground">
												{" "}
												· until {formatDate(grant.plan_ends_at)}
											</span>
										)}
									</span>
									<span className="text-xs text-muted-foreground">
										{formatDateTime(grant.created_at)} ·{" "}
										{grant.by_link ? "Claimed with a Premium link" : "Given directly"}
										{grant.note && ` · ${grant.note}`}
									</span>
								</li>
							))}
						</ul>
					)}
				</section>
				<section aria-labelledby="audit-log" className="flex flex-col gap-3">
					<h2 id="audit-log" className="font-semibold">
						Staff changes
					</h2>
					{workspace.activities.length === 0 ? (
						<p className="text-muted-foreground">No staff changes yet.</p>
					) : (
						<ul className="flex flex-col divide-y surface">
							{workspace.activities.map((activity) => (
								<li key={activity.id} className="flex flex-col gap-0.5 px-4 py-3">
									<span className="font-medium">{activity.type.label}</span>
									<span className="text-xs text-muted-foreground">
										{activity.staff_name} · {formatDateTime(activity.created_at)}
										{typeof activity.properties?.note === "string" &&
											` · ${activity.properties.note}`}
									</span>
								</li>
							))}
						</ul>
					)}
				</section>
			</div>
		</ConsoleLayout>
	)
}

function GivePremiumForm({ workspaceId }: { workspaceId: string }) {
	return (
		<Form
			action={`/staff/workspaces/${workspaceId}/premium`}
			method="post"
			disableWhileProcessing
			resetOnSuccess
			transform={(data) => ({ ...data, days: data.days === "forever" ? null : data.days })}
			className="grid items-start gap-3 sm:grid-cols-[12rem_1fr_auto]"
		>
			{({ errors, processing }) => (
				<>
					<Field data-invalid={errors.days ? true : undefined}>
						<FieldLabel htmlFor="days">Give Premium for</FieldLabel>
						<Select name="days" items={lengths} defaultValue="30">
							<SelectTrigger id="days" className="w-full" {...errorProps("days", errors.days)}>
								<SelectValue />
							</SelectTrigger>
							<SelectContent>
								<SelectGroup>
									{lengths.map((length) => (
										<SelectItem key={length.value} value={length.value}>
											{length.label}
										</SelectItem>
									))}
								</SelectGroup>
							</SelectContent>
						</Select>
						<FieldErrors id="days" error={errors.days} />
					</Field>
					<Field data-invalid={errors.note ? true : undefined}>
						<FieldLabel htmlFor="note">Why</FieldLabel>
						<Input
							id="note"
							name="note"
							maxLength={255}
							placeholder="Beta tester"
							{...errorProps("note", errors.note)}
						/>
						<FieldDescription>Shows in the workspace’s Premium history.</FieldDescription>
						<FieldErrors id="note" error={errors.note} />
					</Field>
					<Button type="submit" disabled={processing} className="sm:mt-6">
						{processing ? <Spinner data-icon="inline-start" /> : <SparklesIcon data-icon="inline-start" />}
						Give Premium
					</Button>
				</>
			)}
		</Form>
	)
}

function EndPremiumButton({ workspaceId, name }: { workspaceId: string; name: string }) {
	const [open, setOpen] = useState(false)
	const [ending, setEnding] = useState(false)

	function end() {
		router.delete(`/staff/workspaces/${workspaceId}/premium`, {
			preserveScroll: true,
			onStart: () => setEnding(true),
			onFinish: () => setEnding(false),
			onSuccess: () => setOpen(false),
		})
	}

	return (
		<>
			<Button variant="outline" className="self-start text-destructive" onClick={() => setOpen(true)}>
				End Premium now
			</Button>
			<AlertDialog open={open} onOpenChange={setOpen}>
				<AlertDialogContent>
					<AlertDialogHeader>
						<AlertDialogTitle>End Premium for {name}?</AlertDialogTitle>
						<AlertDialogDescription>
							It goes back to Free right away and Premium features switch off. Their data stays.
						</AlertDialogDescription>
					</AlertDialogHeader>
					<AlertDialogFooter>
						<AlertDialogCancel disabled={ending}>Cancel</AlertDialogCancel>
						<Button variant="destructive" onClick={end} disabled={ending}>
							{ending && <Spinner data-icon="inline-start" />}
							End Premium
						</Button>
					</AlertDialogFooter>
				</AlertDialogContent>
			</AlertDialog>
		</>
	)
}
