/** A backed enum as the API sends it: its value and how to show it. */
export interface Option<Value extends string = string> {
	value: Value
	label: string
}

export interface StaffMember {
	id: string
	name: string
	email: string
	role: Option<"admin" | "support">
}

/** Props every console page gets from HandleInertiaRequests. */
export interface SharedData {
	staff: StaffMember | null
	flash: string | null
	errors: Record<string, string>
	[key: string]: unknown
}

/** A Laravel paginator, as a resource collection sends it. */
export interface Paginated<Item> {
	data: Item[]
	links: { prev: string | null; next: string | null }
	meta: { current_page: number; last_page: number; from: number | null; to: number | null; total: number }
}

export interface StaffWorkspace {
	id: string
	name: string
	plan: Option<"free" | "premium">
	/** When Premium ends; null on Free, or for Premium with no end date. */
	plan_ends_at: string | null
	owner: { id: string; name: string; email: string } | null
	members_count: number
	leads_count: number
	clients_count: number
	created_at: string
	deleted_at: string | null
	/** When a deleted workspace is removed for good. */
	purged_at: string | null
}

export interface StaffWorkspaceDetail extends StaffWorkspace {
	members: { id: string; name: string; email: string; role: Option<"owner" | "member"> }[]
	grants: {
		id: string
		days: number | null
		plan_ends_at: string | null
		note: string | null
		by_link: boolean
		created_at: string
	}[]
	activities: {
		id: number
		type: Option
		staff_name: string
		properties: Record<string, unknown> | null
		created_at: string
	}[]
}

/** How much of each plan limit a workspace uses; a null limit means no limit. Storage is in bytes. */
export interface WorkspaceUsage {
	seats: { used: number; limit: number }
	forms: { used: number; limit: number | null }
	storage: { used: number; limit: number }
}

export interface StaffPremiumLink {
	id: string
	url: string
	days: number | null
	max_claims: number
	claims: number
	valid_until: string | null
	email: string | null
	note: string | null
	state: Option<"available" | "expired" | "used_up">
	created_at: string
}

export interface StaffUser {
	id: string
	name: string
	email: string
	email_verified: boolean
	two_factor: boolean
	workspaces: { id: string; name: string; role: Option<"owner" | "member"> }[]
	created_at: string
}
