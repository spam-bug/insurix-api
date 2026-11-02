import { Form } from "@inertiajs/react"
import { useState } from "react"

import { errorProps, FieldErrors } from "@/components/field-errors"
import { Button } from "@/components/ui/button"
import { Field, FieldDescription, FieldGroup, FieldLabel } from "@/components/ui/field"
import { Input } from "@/components/ui/input"
import { Spinner } from "@/components/ui/spinner"
import { AuthLayout } from "@/layouts/auth-layout"

/** The second step of signing in: a code from the authenticator app, or a recovery code. */
export default function StaffTwoFactorChallenge() {
	const [useRecoveryCode, setUseRecoveryCode] = useState(false)
	const field = useRecoveryCode ? "recovery_code" : "code"

	return (
		<AuthLayout
			title="Two-factor check"
			description={
				useRecoveryCode
					? "Enter one of the recovery codes you saved. Each one works once."
					: "Enter the 6-digit code from your authenticator app."
			}
		>
			<Form action="/staff/two-factor-challenge" method="post" disableWhileProcessing>
				{({ errors, processing }) => (
					<FieldGroup>
						<Field data-invalid={errors[field] ? true : undefined}>
							<FieldLabel htmlFor={field}>{useRecoveryCode ? "Recovery code" : "Code"}</FieldLabel>
							<Input
								key={field}
								id={field}
								name={field}
								autoComplete="one-time-code"
								inputMode={useRecoveryCode ? "text" : "numeric"}
								required
								autoFocus
								{...errorProps(field, errors[field])}
							/>
							<FieldErrors id={field} error={errors[field]} />
							{!useRecoveryCode && <FieldDescription>Codes change every 30 seconds.</FieldDescription>}
						</Field>
						<Button type="submit" disabled={processing}>
							{processing && <Spinner data-icon="inline-start" />}
							Sign in
						</Button>
						<Button type="button" variant="link" onClick={() => setUseRecoveryCode(!useRecoveryCode)}>
							{useRecoveryCode ? "Use a code from the app instead" : "Lost your phone? Use a recovery code"}
						</Button>
					</FieldGroup>
				)}
			</Form>
		</AuthLayout>
	)
}
