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
