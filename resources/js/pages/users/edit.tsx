import { Form, Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { useState } from 'react';
import type { Value } from 'react-phone-number-input';

import UserController from '@/actions/App/Http/Controllers/UserController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { PhoneInput } from '@/components/ui/phone-input';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { index } from '@/routes/users';

type Role = {
    value: string;
    label: string;
};

type User = {
    id: number;
    name: string;
    email: string;
    role: string;
    phone: string | null;
    color: string | null;
};

type Props = {
    user: User;
    availableRoles: Role[];
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Usuarios',
        href: index().url,
    },
    {
        title: 'Editar',
        href: '#',
    },
];

export default function UsersEdit({ user, availableRoles }: Props) {
    const [phone, setPhone] = useState<Value | undefined>(
        (user.phone as Value) || undefined,
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Editar Usuario" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="icon" asChild>
                        <Link href={index().url}>
                            <ArrowLeft className="h-4 w-4" />
                        </Link>
                    </Button>
                    <div>
                        <h1 className="text-2xl font-semibold">Editar Usuario</h1>
                        <p className="text-muted-foreground text-sm">
                            Modifica la información del usuario
                        </p>
                    </div>
                </div>

                <div className="max-w-2xl rounded-lg border bg-card p-6">
                    <Form
                        {...UserController.update.form.patch(user.id)}
                        options={{
                            preserveScroll: true,
                        }}
                        className="space-y-6"
                        disableWhileProcessing
                    >
                        {({ processing, errors }) => (
                            <>
                                <input type="hidden" name="phone" value={phone || ''} />
                                <div className="space-y-2">
                                    <Label htmlFor="name">
                                        Nombre <span className="text-destructive">*</span>
                                    </Label>
                                    <Input
                                        id="name"
                                        name="name"
                                        defaultValue={user.name}
                                        placeholder="Ej: Juan Pérez"
                                        required
                                    />
                                    <InputError
                                        message={errors.name}
                                        className="mt-2"
                                    />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="email">
                                        Email <span className="text-destructive">*</span>
                                    </Label>
                                    <Input
                                        id="email"
                                        name="email"
                                        type="email"
                                        defaultValue={user.email}
                                        placeholder="usuario@ejemplo.com"
                                        required
                                    />
                                    <InputError
                                        message={errors.email}
                                        className="mt-2"
                                    />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="password">
                                        Nueva Contraseña
                                    </Label>
                                    <Input
                                        id="password"
                                        name="password"
                                        type="password"
                                        placeholder="Dejar vacío para mantener la actual"
                                    />
                                    <p className="text-muted-foreground text-xs">
                                        Deja este campo vacío si no deseas cambiar la
                                        contraseña
                                    </p>
                                    <InputError
                                        message={errors.password}
                                        className="mt-2"
                                    />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="password_confirmation">
                                        Confirmar Nueva Contraseña
                                    </Label>
                                    <Input
                                        id="password_confirmation"
                                        name="password_confirmation"
                                        type="password"
                                        placeholder="Repite la nueva contraseña"
                                    />
                                    <InputError
                                        message={errors.password_confirmation}
                                        className="mt-2"
                                    />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="phone">Teléfono</Label>
                                    <PhoneInput
                                        id="phone"
                                        value={phone}
                                        onChange={(value) => setPhone(value)}
                                        defaultCountry="CO"
                                    />
                                    <InputError
                                        message={errors.phone}
                                        className="mt-2"
                                    />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="color">Color</Label>
                                    <div className="flex gap-2">
                                        <Input
                                            id="color-picker"
                                            type="color"
                                            defaultValue={user.color || '#5D69F7'}
                                            className="h-10 w-20 cursor-pointer"
                                            onChange={(e) => {
                                                const textInput = document.getElementById('color') as HTMLInputElement;
                                                if (textInput) {
                                                    textInput.value = e.target.value;
                                                }
                                            }}
                                        />
                                        <Input
                                            id="color"
                                            name="color"
                                            type="text"
                                            defaultValue={user.color || '#5D69F7'}
                                            placeholder="#5D69F7"
                                            pattern="^#[0-9A-Fa-f]{6}$"
                                            className="flex-1"
                                            onChange={(e) => {
                                                const colorPicker = document.getElementById('color-picker') as HTMLInputElement;
                                                if (colorPicker && /^#[0-9A-Fa-f]{6}$/.test(e.target.value)) {
                                                    colorPicker.value = e.target.value;
                                                }
                                            }}
                                        />
                                    </div>
                                    <InputError
                                        message={errors.color}
                                        className="mt-2"
                                    />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="role">
                                        Rol <span className="text-destructive">*</span>
                                    </Label>
                                    <select
                                        id="role"
                                        name="role"
                                        defaultValue={user.role}
                                        required
                                        className="flex h-9 w-full items-center justify-between rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        {availableRoles.map((role) => (
                                            <option key={role.value} value={role.value}>
                                                {role.label}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError
                                        message={errors.role}
                                        className="mt-2"
                                    />
                                </div>

                                <div className="flex gap-4">
                                    <Button
                                        type="submit"
                                        disabled={processing}
                                    >
                                        {processing
                                            ? 'Guardando...'
                                            : 'Guardar Cambios'}
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        asChild
                                    >
                                        <Link href={index().url}>
                                            Cancelar
                                        </Link>
                                    </Button>
                                </div>
                            </>
                        )}
                    </Form>
                </div>
            </div>
        </AppLayout>
    );
}
