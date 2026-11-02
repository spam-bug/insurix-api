import { Form } from "@inertiajs/react"

import { errorProps, FieldErrors } from "@/components/field-errors"
import { Button } from "@/components/ui/button"
import { Field, FieldGroup, FieldLabel } from "@/components/ui/field"
import { Input } from "@/components/ui/input"
import { Spinner } from "@/components/ui/spinner"
import { AuthLayout } from "@/layouts/auth-layout"

export default function StaffLogin() {
	return (
		<AuthLayout title="Sign in" description="The staff console is for the Insurix team.">
			<Form action="/staff/login" method="post" disableWhileProcessing resetOnError={["password"]}>
				{({ errors, processing }) => (
					<FieldGroup>
						<Field data-invalid={errors.email ? true : undefined}>
							<FieldLabel htmlFor="email">Work email</FieldLabel>
							<Input
								id="email"
								name="email"
								type="email"
								autoComplete="username"
								required
								autoFocus
								{...errorProps("email", errors.email)}
							/>
							<FieldErrors id="email" error={errors.email} />
						</Field>
						<Field data-invalid={errors.password ? true : undefined}>
							<FieldLabel htmlFor="password">Password</FieldLabel>
							<Input
								id="password"
								name="password"
								type="password"
								autoComplete="current-password"
								required
								{...errorProps("password", errors.password)}
							/>
							<FieldErrors id="password" error={errors.password} />
						</Field>
						<Button type="submit" disabled={processing}>
							{processing && <Spinner data-icon="inline-start" />}
							Continue
						</Button>
					</FieldGroup>
				)}
			</Form>
		</AuthLayout>
	)
}
