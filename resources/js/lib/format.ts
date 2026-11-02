const dateFormat = new Intl.DateTimeFormat("en", { month: "short", day: "numeric", year: "numeric" })
const dateTimeFormat = new Intl.DateTimeFormat("en", {
	month: "short",
	day: "numeric",
	year: "numeric",
	hour: "numeric",
	minute: "2-digit",
})
const countFormat = new Intl.NumberFormat("en")

export const formatDate = (iso: string) => dateFormat.format(new Date(iso))
export const formatDateTime = (iso: string) => dateTimeFormat.format(new Date(iso))
export const formatCount = (count: number) => countFormat.format(count)

/** 1536 → "1.5 KB", up to GB. */
export function formatBytes(bytes: number): string {
	const units = ["B", "KB", "MB", "GB"]
	let value = bytes
	let unit = 0
	while (value >= 1024 && unit < units.length - 1) {
		value /= 1024
		unit++
	}
	return `${value >= 10 || unit === 0 ? Math.round(value) : value.toFixed(1)} ${units[unit]}`
}

/** "Premium until Dec 2, 2026", "Premium, no end date" or "Free". */
export function planText(plan: { value: string; label: string }, endsAt: string | null): string {
	if (plan.value !== "premium") return plan.label
	return endsAt ? `${plan.label} until ${formatDate(endsAt)}` : `${plan.label}, no end date`
}
