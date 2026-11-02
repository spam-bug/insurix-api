import { Form, Link } from "@inertiajs/react"

import { errorProps, FieldErrors } from "@/components/field-errors"
import { Button } from "@/components/ui/button"
import { Field, FieldDescription, FieldGroup, FieldLabel } from "@/components/ui/field"
import { Input } from "@/components/ui/input"
import { Spinner } from "@/components/ui/spinner"
import { AuthLayout } from "@/layouts/auth-layout"

interface StaffTwoFactorSetupProps {
	confirmed: boolean
	qr_code_svg: string | null
	secret: string | null
	recovery_codes: string[]
}

/**
 * Required before the console opens: scan the code, confirm it with the first code from the
 * app, then save the recovery codes.
 */
export default function StaffTwoFactorSetup({ confirmed, qr_code_svg, secret, recovery_codes }: StaffTwoFactorSetupProps) {
	if (confirmed) {
		return (
			<AuthLayout
				title="Save your recovery codes"
				description="If you lose your phone, each of these signs you in once. Keep them somewhere safe, like a password manager."
			>
				<div className="flex flex-col gap-5">
					<ul className="grid grid-cols-2 gap-2 rounded-lg bg-muted p-3 font-mono text-sm" aria-label="Recovery codes">
						{recovery_codes.map((code) => (
							<li key={code}>{code}</li>
						))}
					</ul>
					<Button nativeButton={false} render={<Link href="/staff/workspaces" />}>
						I’ve saved them
					</Button>
				</div>
			</AuthLayout>
		)
	}

	return (
		<AuthLayout
			title="Set up two-factor"
			description="The staff console needs a second step when you sign in. Scan this with an authenticator app, like Google Authenticator or 1Password."
		>
			<Form action="/staff/two-factor" method="post" disableWhileProcessing resetOnError>
				{({ errors, processing }) => (
					<FieldGroup>
						{qr_code_svg && (
							<div
								className="mx-auto rounded-lg bg-white p-3 [&_svg]:size-44"
								role="img"
								aria-label="QR code to scan with your authenticator app"
								dangerouslySetInnerHTML={{ __html: qr_code_svg }}
							/>
						)}
						{secret && (
							<p className="text-center text-xs text-muted-foreground">
								Can’t scan it? Enter this key: <span className="font-mono text-foreground select-all">{secret}</span>
							</p>
						)}
						<Field data-invalid={errors.code ? true : undefined}>
							<FieldLabel htmlFor="code">Code from the app</FieldLabel>
							<Input
								id="code"
								name="code"
								autoComplete="one-time-code"
								inputMode="numeric"
								required
								autoFocus
								{...errorProps("code", errors.code)}
							/>
							<FieldErrors id="code" error={errors.code} />
							<FieldDescription>Enter the 6-digit code it shows to finish.</FieldDescription>
						</Field>
						<Button type="submit" disabled={processing}>
							{processing && <Spinner data-icon="inline-start" />}
							Turn on two-factor
						</Button>
					</FieldGroup>
				)}
			</Form>
		</AuthLayout>
	)
}
