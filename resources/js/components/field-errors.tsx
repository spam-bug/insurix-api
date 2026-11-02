import { FieldError } from "@/components/ui/field"

/** aria props that tie an input to its error message. */
export function errorProps(id: string, error?: string) {
	return error ? { "aria-invalid": true, "aria-describedby": `${id}-error` } : {}
}

interface FieldErrorsProps {
	id: string
	error?: string
}

/** A field's validation message from Inertia, linked to its input through errorProps. */
export function FieldErrors({ id, error }: FieldErrorsProps) {
	return <FieldError id={`${id}-error`} errors={error ? [{ message: error }] : undefined} />
}
