import { Head, Link, router } from '@inertiajs/react';
import { Edit, Plus, Trash2, User as UserIcon } from 'lucide-react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, PaginatedData } from '@/types';
import { create, destroy, index } from '@/routes/users';

type User = {
    id: number;
    name: string;
    email: string;
    role: string;
    phone: string | null;
    color: string | null;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
};

type Props = {
    users: PaginatedData<User>;
    canCreate: boolean;
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Usuarios',
        href: index().url,
    },
];

const getRoleBadgeVariant = (role: string) => {
    switch (role) {
        case 'mango':
            return 'default';
        case 'admin':
            return 'secondary';
        default:
            return 'outline';
    }
};

const getRoleLabel = (role: string) => {
    switch (role) {
        case 'mango':
            return 'Mango';
        case 'admin':
            return 'Administrador';
        default:
            return 'Usuario';
    }
};

export default function UsersIndex({ users, canCreate }: Props) {
    const handleDelete = (id: number) => {
        router.delete(destroy(id).url, {
            preserveScroll: true,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Usuarios" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">Usuarios</h1>
                        <p className="text-muted-foreground text-sm">
                            Gestiona los usuarios del sistema
                        </p>
                    </div>
                    {canCreate && (
                        <Button asChild>
                            <Link href={create().url}>
                                <Plus className="mr-2 h-4 w-4" />
                                Nuevo Usuario
                            </Link>
                        </Button>
                    )}
                </div>

                <div className="rounded-lg border bg-card">
                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead>
                                <tr className="border-b bg-muted/50">
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted-foreground">
                                        Nombre
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted-foreground">
                                        Email
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted-foreground">
                                        Rol
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted-foreground">
                                        Teléfono
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted-foreground">
                                        Color
                                    </th>
                                    <th className="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted-foreground">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {users.data.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={6}
                                            className="px-6 py-8 text-center text-muted-foreground"
                                        >
                                            <UserIcon className="mx-auto mb-2 h-12 w-12 opacity-20" />
                                            <p>No hay usuarios registrados</p>
                                        </td>
                                    </tr>
                                ) : (
                                    users.data.map((user: User) => (
                                        <tr
                                            key={user.id}
                                            className="hover:bg-muted/50 transition-colors"
                                        >
                                            <td className="px-6 py-4">
                                                <div className="font-medium">
                                                    {user.name}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 text-sm">
                                                {user.email}
                                            </td>
                                            <td className="px-6 py-4">
                                                <Badge
                                                    variant={getRoleBadgeVariant(
                                                        user.role,
                                                    )}
                                                >
                                                    {getRoleLabel(user.role)}
                                                </Badge>
                                            </td>
                                            <td className="px-6 py-4 text-sm">
                                                {user.phone || (
                                                    <span className="text-muted-foreground">
                                                        Sin teléfono
                                                    </span>
                                                )}
                                            </td>
                                            <td className="px-6 py-4">
                                                {user.color ? (
                                                    <div className="flex items-center gap-2">
                                                        <div
                                                            className="h-6 w-6 rounded border"
                                                            style={{
                                                                backgroundColor:
                                                                    user.color,
                                                            }}
                                                        />
                                                        <span className="text-muted-foreground text-xs">
                                                            {user.color}
                                                        </span>
                                                    </div>
                                                ) : (
                                                    <span className="text-muted-foreground text-sm">
                                                        Sin color
                                                    </span>
                                                )}
                                            </td>
                                            <td className="px-6 py-4 text-right">
                                                <div className="flex items-center justify-end gap-2">
                                                    {canCreate && (
                                                        <>
                                                            <Button
                                                                variant="ghost"
                                                                size="icon"
                                                                asChild
                                                            >
                                                                <Link
                                                                    href={`/users/${user.id}/edit`}
                                                                >
                                                                    <Edit className="h-4 w-4" />
                                                                </Link>
                                                            </Button>
                                                            <Dialog>
                                                                <DialogTrigger asChild>
                                                                    <Button
                                                                        variant="ghost"
                                                                        size="icon"
                                                                    >
                                                                        <Trash2 className="h-4 w-4 text-destructive" />
                                                                    </Button>
                                                                </DialogTrigger>
                                                                <DialogContent>
                                                                    <DialogHeader>
                                                                        <DialogTitle>
                                                                            ¿Eliminar
                                                                            usuario?
                                                                        </DialogTitle>
                                                                        <DialogDescription>
                                                                            Esta
                                                                            acción
                                                                            no
                                                                            se
                                                                            puede
                                                                            deshacer.
                                                                            Se
                                                                            eliminará
                                                                            el
                                                                            usuario
                                                                            "{user.name}".
                                                                        </DialogDescription>
                                                                    </DialogHeader>
                                                                    <DialogFooter>
                                                                        <DialogClose asChild>
                                                                            <Button
                                                                                variant="outline"
                                                                            >
                                                                                Cancelar
                                                                            </Button>
                                                                        </DialogClose>
                                                                        <Button
                                                                            variant="destructive"
                                                                            onClick={() =>
                                                                                handleDelete(
                                                                                    user.id,
                                                                                )
                                                                            }
                                                                        >
                                                                            Eliminar
                                                                        </Button>
                                                                    </DialogFooter>
                                                                </DialogContent>
                                                            </Dialog>
                                                        </>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>

                    {users.links && users.links.length > 3 && (
                        <div className="border-t px-6 py-4">
                            <div className="flex items-center justify-between">
                                <div className="text-muted-foreground text-sm">
                                    Mostrando {users.from} a {users.to} de{' '}
                                    {users.total} resultados
                                </div>
                                <div className="flex gap-2">
                                    {users.links.map((link: { url: string | null; label: string; active: boolean }, index: number) => (
                                        <Button
                                            key={index}
                                            variant={
                                                link.active
                                                    ? 'default'
                                                    : 'outline'
                                            }
                                            size="sm"
                                            asChild
                                            disabled={!link.url}
                                        >
                                            <Link
                                                href={link.url || '#'}
                                                dangerouslySetInnerHTML={{
                                                    __html: link.label,
                                                }}
                                            />
                                        </Button>
                                    ))}
                                </div>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
