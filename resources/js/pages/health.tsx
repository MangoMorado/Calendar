import { Head } from '@inertiajs/react';
import {
    Activity,
    Calendar,
    Database,
    FileText,
    HardDrive,
    Layers,
    Server,
    Users,
} from 'lucide-react';

import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { dashboard, health } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type MetricStatus = 'healthy' | 'unhealthy' | 'unknown';

type MetricItem = {
    status: MetricStatus;
    message: string;
    driver?: string;
    size_mb?: number;
};

type Metrics = {
    environment: string;
    php_version: string;
    laravel_version: string;
    timezone: string;
    database: MetricItem;
    cache: MetricItem;
    storage: MetricItem;
    counts: {
        users: number;
        calendars: number;
        appointments: number;
        notes: number;
    };
    queue_connection: string;
};

type Props = {
    metrics: Metrics;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Salud', href: health().url },
];

function StatusBadge({ status }: { status: MetricStatus }) {
    const variants: Record<MetricStatus, string> = {
        healthy: 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400',
        unhealthy: 'bg-red-500/15 text-red-600 dark:text-red-400',
        unknown: 'bg-amber-500/15 text-amber-600 dark:text-amber-400',
    };
    const labels: Record<MetricStatus, string> = {
        healthy: 'Correcto',
        unhealthy: 'Error',
        unknown: 'Desconocido',
    };
    return (
        <Badge variant="outline" className={variants[status]}>
            {labels[status]}
        </Badge>
    );
}

function MetricCard({
    title,
    description,
    icon: Icon,
    status,
    details,
}: {
    title: string;
    description?: string;
    icon: React.ElementType;
    status?: MetricStatus;
    details?: React.ReactNode;
}) {
    return (
        <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <div>
                    <CardTitle className="text-sm font-medium">{title}</CardTitle>
                    {description && (
                        <CardDescription>{description}</CardDescription>
                    )}
                </div>
                <Icon className="h-8 w-8 text-muted-foreground" />
            </CardHeader>
            <CardContent>
                {status && <StatusBadge status={status} />}
                {details}
            </CardContent>
        </Card>
    );
}

export default function Health({ metrics }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Salud - Dashboard de Monitoreo" />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-auto rounded-xl p-4">
                <div>
                    <h1 className="text-2xl font-semibold">Salud de la Aplicación</h1>
                    <p className="text-muted-foreground text-sm">
                        Monitoreo del estado de los servicios y métricas del sistema
                    </p>
                </div>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <MetricCard
                        title="Entorno"
                        icon={Layers}
                        details={
                            <p className="mt-2 text-2xl font-bold capitalize">
                                {metrics.environment}
                            </p>
                        }
                    />
                    <MetricCard
                        title="PHP"
                        icon={Server}
                        details={
                            <p className="mt-2 text-2xl font-bold">
                                {metrics.php_version}
                            </p>
                        }
                    />
                    <MetricCard
                        title="Laravel"
                        icon={Layers}
                        details={
                            <p className="mt-2 text-2xl font-bold">
                                v{metrics.laravel_version}
                            </p>
                        }
                    />
                </div>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <MetricCard
                        title="Base de Datos"
                        description={metrics.database.driver}
                        icon={Database}
                        status={metrics.database.status}
                        details={
                            <p className="mt-2 text-sm text-muted-foreground">
                                {metrics.database.message}
                            </p>
                        }
                    />
                    <MetricCard
                        title="Cache"
                        description={metrics.cache.driver}
                        icon={Activity}
                        status={metrics.cache.status}
                        details={
                            <p className="mt-2 text-sm text-muted-foreground">
                                {metrics.cache.message}
                            </p>
                        }
                    />
                    <MetricCard
                        title="Almacenamiento"
                        icon={HardDrive}
                        status={metrics.storage.status}
                        details={
                            <div className="mt-2 space-y-1">
                                <p className="text-sm text-muted-foreground">
                                    {metrics.storage.message}
                                </p>
                                {metrics.storage.size_mb != null && (
                                    <p className="text-lg font-semibold">
                                        {metrics.storage.size_mb} MB
                                    </p>
                                )}
                            </div>
                        }
                    />
                </div>

                <div>
                    <h2 className="mb-4 text-lg font-semibold">Datos de la Aplicación</h2>
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <MetricCard
                            title="Usuarios"
                            icon={Users}
                            details={
                                <p className="mt-2 text-2xl font-bold">
                                    {metrics.counts.users}
                                </p>
                            }
                        />
                        <MetricCard
                            title="Calendarios"
                            icon={Calendar}
                            details={
                                <p className="mt-2 text-2xl font-bold">
                                    {metrics.counts.calendars}
                                </p>
                            }
                        />
                        <MetricCard
                            title="Citas"
                            icon={Calendar}
                            details={
                                <p className="mt-2 text-2xl font-bold">
                                    {metrics.counts.appointments}
                                </p>
                            }
                        />
                        <MetricCard
                            title="Notas"
                            icon={FileText}
                            details={
                                <p className="mt-2 text-2xl font-bold">
                                    {metrics.counts.notes}
                                </p>
                            }
                        />
                    </div>
                </div>

                <div className="rounded-lg border bg-muted/30 p-4">
                    <p className="text-muted-foreground text-sm">
                        <span className="font-medium">Cola:</span>{' '}
                        {metrics.queue_connection} ·{' '}
                        <span className="font-medium">Zona horaria:</span>{' '}
                        {metrics.timezone}
                    </p>
                </div>
            </div>
        </AppLayout>
    );
}
