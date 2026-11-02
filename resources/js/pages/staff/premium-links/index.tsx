import { Form } from "@inertiajs/react"
import { useState } from "react"
import { toast } from "sonner"
import { CopyIcon, PlusIcon, SparklesIcon } from "lucide-react"

import { EmptyState } from "@/components/empty-state"
import { errorProps, FieldErrors } from "@/components/field-errors"
import { Pager } from "@/components/pager"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import {
	Dialog,
	DialogClose,
	DialogContent,
	DialogDescription,
	DialogFooter,
	DialogHeader,
	DialogTitle,
} from "@/components/ui/dialog"
import { Field, FieldDescription, FieldGroup, FieldLabel } from "@/components/ui/field"
import { Input } from "@/components/ui/input"
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Spinner } from "@/components/ui/spinner"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { Tooltip, TooltipContent, TooltipTrigger } from "@/components/ui/tooltip"
import { ConsoleLayout } from "@/layouts/console-layout"
import { formatDate } from "@/lib/format"
import type { Paginated, StaffPremiumLink } from "@/types"

interface StaffPremiumLinksProps {
	links: Paginated<StaffPremiumLink>
	can_change: boolean
}

const lengths = [
	{ value: "30", label: "30 days" },
	{ value: "90", label: "90 days" },
	{ value: "180", label: "6 months" },
	{ value: "365", label: "1 year" },
	{ value: "forever", label: "No end date" },
]

const stateVariants = { available: "success", expired: "outline", used_up: "outline" } as const

/** Links that give Premium to whoever claims them, for beta testers and partners. */
export default function StaffPremiumLinks({ links, can_change }: StaffPremiumLinksProps) {
	const [creating, setCreating] = useState(false)

	function copy(url: string) {
		navigator.clipboard.writeText(url).then(
			() => toast.success("Link copied"),
			() => toast.error("Couldn’t copy the link. Select it and copy it instead."),
		)
	}

	return (
		<ConsoleLayout
			title="Premium links"
			description="Share a link to give Premium to beta testers or partners. Agents claim it for a workspace they own."
			actions={
				can_change && (
					<Button onClick={() => setCreating(true)}>
						<PlusIcon data-icon="inline-start" />
						New link
					</Button>
				)
			}
		>
			{links.data.length === 0 ? (
				<EmptyState
					icon={<SparklesIcon />}
					title="No Premium links yet"
					description={can_change ? "Make one with New link." : "Admins can make them."}
				/>
			) : (
				<div className="surface overflow-x-auto">
					<Table>
						<TableHeader>
							<TableRow>
								<TableHead>For</TableHead>
								<TableHead>Gives</TableHead>
								<TableHead className="text-right">Claimed</TableHead>
								<TableHead>Valid until</TableHead>
								<TableHead>Status</TableHead>
								<TableHead>
									<span className="sr-only">Copy</span>
								</TableHead>
							</TableRow>
						</TableHeader>
						<TableBody>
							{links.data.map((link) => (
								<TableRow key={link.id}>
									<TableCell>
										<span className="font-medium">{link.note ?? "No note"}</span>
										<span className="block text-xs text-muted-foreground">
											{link.email ? `Only ${link.email}` : "Anyone with the link"} · made{" "}
											{formatDate(link.created_at)}
										</span>
									</TableCell>
									<TableCell>{link.days ? `${link.days} days` : "No end date"}</TableCell>
									<TableCell className="text-right tabular-nums">
										{link.claims} of {link.max_claims}
									</TableCell>
									<TableCell className="text-muted-foreground">
										{link.valid_until ? formatDate(link.valid_until) : "No limit"}
									</TableCell>
									<TableCell>
										<Badge variant={stateVariants[link.state.value]}>{link.state.label}</Badge>
									</TableCell>
									<TableCell className="text-right">
										<Tooltip>
											<TooltipTrigger
												render={
													<Button
														variant="ghost"
														size="icon-sm"
														aria-label={`Copy the link${link.note ? ` for ${link.note}` : ""}`}
														onClick={() => copy(link.url)}
													/>
												}
											>
												<CopyIcon />
											</TooltipTrigger>
											<TooltipContent>Copy link</TooltipContent>
										</Tooltip>
									</TableCell>
								</TableRow>
							))}
						</TableBody>
					</Table>
				</div>
			)}
			<Pager page={links} noun="links" />

			<Dialog open={creating} onOpenChange={setCreating}>
				<DialogContent>
					<DialogHeader>
						<DialogTitle>New Premium link</DialogTitle>
						<DialogDescription>It shows up in the list, ready to copy and share.</DialogDescription>
					</DialogHeader>
					<Form
						action="/staff/premium-links"
						method="post"
						disableWhileProcessing
						onSuccess={() => setCreating(false)}
						// The last day counts in full, in the viewer's own time.
						transform={(data) => ({
							...data,
							days: data.days === "forever" ? null : data.days,
							valid_until: data.valid_until
								? new Date(`${data.valid_until}T23:59:59`).toISOString()
								: null,
						})}
					>
						{({ errors, processing }) => (
							<FieldGroup>
								<div className="grid gap-4 sm:grid-cols-2">
									<Field data-invalid={errors.days ? true : undefined}>
										<FieldLabel htmlFor="link-days">Gives Premium for</FieldLabel>
										<Select name="days" items={lengths} defaultValue="90">
											<SelectTrigger
												id="link-days"
												className="w-full"
												{...errorProps("link-days", errors.days)}
											>
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
										<FieldErrors id="link-days" error={errors.days} />
									</Field>
									<Field data-invalid={errors.max_claims ? true : undefined}>
										<FieldLabel htmlFor="max_claims">How many can claim it</FieldLabel>
										<Input
											id="max_claims"
											name="max_claims"
											type="number"
											min={1}
											max={1000}
											defaultValue={1}
											required
											{...errorProps("max_claims", errors.max_claims)}
										/>
										<FieldErrors id="max_claims" error={errors.max_claims} />
									</Field>
								</div>
								<Field data-invalid={errors.valid_until ? true : undefined}>
									<FieldLabel htmlFor="valid_until">Claim by</FieldLabel>
									<Input
										id="valid_until"
										name="valid_until"
										type="date"
										{...errorProps("valid_until", errors.valid_until)}
									/>
									<FieldDescription>Leave empty for no deadline.</FieldDescription>
									<FieldErrors id="valid_until" error={errors.valid_until} />
								</Field>
								<Field data-invalid={errors.email ? true : undefined}>
									<FieldLabel htmlFor="email">Only for this email</FieldLabel>
									<Input
										id="email"
										name="email"
										type="email"
										placeholder="agent@example.com"
										{...errorProps("email", errors.email)}
									/>
									<FieldDescription>
										Leave empty so anyone with the link can claim it.
									</FieldDescription>
									<FieldErrors id="email" error={errors.email} />
								</Field>
								<Field data-invalid={errors.note ? true : undefined}>
									<FieldLabel htmlFor="link-note">Note</FieldLabel>
									<Input
										id="link-note"
										name="note"
										maxLength={255}
										placeholder="Beta testers, November"
										{...errorProps("link-note", errors.note)}
									/>
									<FieldErrors id="link-note" error={errors.note} />
								</Field>
								<DialogFooter>
									<DialogClose render={<Button variant="outline" />} disabled={processing}>
										Cancel
									</DialogClose>
									<Button type="submit" disabled={processing}>
										{processing && <Spinner data-icon="inline-start" />}
										Make link
									</Button>
								</DialogFooter>
							</FieldGroup>
						)}
					</Form>
				</DialogContent>
			</Dialog>
		</ConsoleLayout>
	)
}
