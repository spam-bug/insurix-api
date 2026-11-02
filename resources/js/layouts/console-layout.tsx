import { Head, Link, usePage } from "@inertiajs/react"
import { useEffect, type ReactNode } from "react"
import { toast } from "sonner"
import { BuildingIcon, LogOutIcon, SparklesIcon, UsersIcon, type LucideIcon } from "lucide-react"

import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { Separator } from "@/components/ui/separator"
import {
	Sidebar,
	SidebarContent,
	SidebarFooter,
	SidebarGroup,
	SidebarGroupContent,
	SidebarHeader,
	SidebarInset,
	SidebarMenu,
	SidebarMenuButton,
	SidebarMenuItem,
	SidebarProvider,
	SidebarTrigger,
} from "@/components/ui/sidebar"
import { Toaster } from "@/components/ui/sonner"
import { TooltipProvider } from "@/components/ui/tooltip"
import type { SharedData } from "@/types"

const navigation: { title: string; href: string; icon: LucideIcon }[] = [
	{ title: "Workspaces", href: "/staff/workspaces", icon: BuildingIcon },
	{ title: "Premium links", href: "/staff/premium-links", icon: SparklesIcon },
	{ title: "Users", href: "/staff/users", icon: UsersIcon },
]

interface ConsoleLayoutProps {
	title: string
	description?: ReactNode
	/** Buttons beside the title, such as "New link". */
	actions?: ReactNode
	children: ReactNode
}

/** The signed-in console: navigation on the left, the page's title and content on the right. */
export function ConsoleLayout({ title, description, actions, children }: ConsoleLayoutProps) {
	const page = usePage<SharedData>()
	const { staff, flash } = page.props

	// A message from the action that just ran, such as "Premium given". Keyed on the page, so the
	// same message shows again after the same action.
	useEffect(() => {
		if (flash) toast.success(flash)
	}, [page, flash])

	return (
		<TooltipProvider>
			<SidebarProvider>
				<Head title={title} />
				<Sidebar>
					<SidebarHeader className="px-4 py-3">
						<span className="text-sm font-semibold">Suvvi</span>
						<span className="label-caps">Staff console</span>
					</SidebarHeader>
					<SidebarContent>
						<SidebarGroup>
							<SidebarGroupContent>
								<SidebarMenu>
									{navigation.map((item) => (
										<SidebarMenuItem key={item.href}>
											<SidebarMenuButton
												isActive={page.url.startsWith(item.href)}
												render={<Link href={item.href} prefetch />}
											>
												<item.icon />
												{item.title}
											</SidebarMenuButton>
										</SidebarMenuItem>
									))}
								</SidebarMenu>
							</SidebarGroupContent>
						</SidebarGroup>
					</SidebarContent>
					{staff && (
						<SidebarFooter className="gap-2 p-3">
							<div className="flex flex-col gap-1 px-1">
								<span className="truncate text-sm font-medium">{staff.name}</span>
								<span className="flex items-center gap-2 truncate text-xs">
									<Badge variant="outline">{staff.role.label}</Badge>
									{staff.email}
								</span>
							</div>
							<Button
								variant="ghost"
								size="sm"
								className="justify-start"
								nativeButton={false}
								render={<Link href="/staff/logout" method="post" as="button" />}
							>
								<LogOutIcon data-icon="inline-start" />
								Sign out
							</Button>
						</SidebarFooter>
					)}
				</Sidebar>
				<SidebarInset className="min-w-0 bg-background">
					<header className="flex h-14 shrink-0 items-center gap-3 px-4 md:px-6">
						<SidebarTrigger className="-ml-1" />
						<Separator orientation="vertical" className="data-vertical:h-5 data-vertical:self-auto" />
						<span className="text-sm text-muted-foreground">Staff console</span>
					</header>
					<main className="flex w-full max-w-6xl flex-col gap-6 px-4 pb-10 md:px-7">
						<div className="flex flex-wrap items-end justify-between gap-3">
							<div className="flex flex-col gap-1">
								<h1 className="text-xl font-semibold tracking-tight">{title}</h1>
								{description && <p className="text-muted-foreground">{description}</p>}
							</div>
							{actions}
						</div>
						{children}
					</main>
				</SidebarInset>
				<Toaster />
			</SidebarProvider>
		</TooltipProvider>
	)
}
