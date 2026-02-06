import { Head, router } from '@inertiajs/react';
import {
    BarChart3,
    Calendar,
    Clock,
    Filter,
    Target,
    TrendingUp,
} from 'lucide-react';
import { useState } from 'react';
import {
    Area,
    AreaChart,
    Bar,
    BarChart,
    CartesianGrid,
    Cell,
    Pie,
    PieChart,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';

import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { analytics, dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type MonthData = { month: number; year: number; count: number };
type PeakHourData = { hour: number; count: number };
type WeekdayData = { weekday: number; name: string; count: number };
type BalanceCalendar = { calendar_id: number; calendar_name: string; count: number; hours: number };
type BalanceUser = { user_id: number | null; user_name: string; count: number; hours: number };

type Props = {
    filters: { start_date: string; end_date: string };
    totalAppointments: number;
    timeBalanceHours: number;
    appointmentsByMonth: MonthData[];
    balanceByCalendar: BalanceCalendar[];
    balanceByUser: BalanceUser[];
    avgPerDay: number;
    avgPerMonth: number;
    avgPerYear: number;
    peakHours: PeakHourData[];
    distributionByWeekday: WeekdayData[];
    mostActiveWeekday: string;
    avgDurationMinutes: number;
    allDayCount: number;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Analíticas', href: analytics().url },
];

const MONTHS = [
    'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun',
    'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic',
];

const COLORS = [
    '#6366f1', '#8b5cf6', '#a855f7', '#d946ef', '#ec4899',
    '#f43f5e', '#f97316', '#eab308', '#22c55e', '#14b8a6',
];

function KpiCard({
    title,
    value,
    icon: Icon,
    description,
}: {
    title: string;
    value: string | number;
    icon: React.ElementType;
    description?: string;
}) {
    return (
        <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium">{title}</CardTitle>
                <Icon className="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
                <div className="text-2xl font-bold">{value}</div>
                {description && (
                    <p className="text-muted-foreground text-xs">{description}</p>
                )}
            </CardContent>
        </Card>
    );
}

export default function Analytics(props: Props) {
    const [startDate, setStartDate] = useState(props.filters.start_date);
    const [endDate, setEndDate] = useState(props.filters.end_date);

    const handleApplyFilter = () => {
        router.get(analytics().url, { start_date: startDate, end_date: endDate });
    };

    const monthChartData = props.appointmentsByMonth.map((d) => ({
        name: `${MONTHS[d.month - 1]} ${d.year}`,
        citas: d.count,
    }));

    const peakChartData = props.peakHours.map((d) => ({
        name: `${String(d.hour).padStart(2, '0')}:00`,
        citas: d.count,
    }));

    const weekdayChartData = props.distributionByWeekday.map((d) => ({
        name: d.name,
        citas: d.count,
        fill: COLORS[d.weekday % COLORS.length],
    }));

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Analíticas - Estadísticas de Citas" />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-auto rounded-xl p-4">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">Analíticas de Citas</h1>
                        <p className="text-muted-foreground text-sm">
                            Estadísticas, balances y distribución de citas
                        </p>
                    </div>
                    <div className="flex flex-wrap items-end gap-3">
                        <div className="space-y-2">
                            <Label htmlFor="start_date" className="text-xs">Desde</Label>
                            <Input
                                id="start_date"
                                type="date"
                                value={startDate}
                                onChange={(e) => setStartDate(e.target.value)}
                                className="h-9"
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="end_date" className="text-xs">Hasta</Label>
                            <Input
                                id="end_date"
                                type="date"
                                value={endDate}
                                onChange={(e) => setEndDate(e.target.value)}
                                className="h-9"
                            />
                        </div>
                        <Button onClick={handleApplyFilter} size="sm" className="gap-2">
                            <Filter className="h-4 w-4" />
                            Aplicar
                        </Button>
                    </div>
                </div>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                    <KpiCard
                        title="Total citas"
                        value={props.totalAppointments}
                        icon={Calendar}
                    />
                    <KpiCard
                        title="Horas totales"
                        value={props.timeBalanceHours.toFixed(1)}
                        icon={Clock}
                        description="Tiempo en citas"
                    />
                    <KpiCard
                        title="Promedio/día"
                        value={props.avgPerDay}
                        icon={TrendingUp}
                    />
                    <KpiCard
                        title="Promedio/mes"
                        value={props.avgPerMonth}
                        icon={BarChart3}
                    />
                    <KpiCard
                        title="Duración media"
                        value={`${props.avgDurationMinutes} min`}
                        icon={Target}
                    />
                    <KpiCard
                        title="Día más activo"
                        value={props.mostActiveWeekday}
                        icon={Calendar}
                    />
                </div>

                <Card>
                    <CardHeader className="flex flex-row items-start justify-between space-y-0">
                        <div>
                            <CardTitle>Estadísticas de Citas</CardTitle>
                            <CardDescription>Distribución mensual en el rango seleccionado</CardDescription>
                        </div>
                        <div className="text-right">
                            <p className="text-muted-foreground text-xs">Citas agendadas (total)</p>
                            <p className="text-2xl font-bold">{props.totalAppointments.toLocaleString()}</p>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div className="h-[280px] w-full min-w-0">
                            <ResponsiveContainer width="100%" height={280} minHeight={280}>
                                <AreaChart data={monthChartData} margin={{ top: 10, right: 10, left: 0, bottom: 0 }}>
                                    <defs>
                                        <linearGradient id="areaGradient" x1="0" y1="0" x2="0" y2="1">
                                            <stop offset="0%" stopColor="#3b82f6" stopOpacity={0.4} />
                                            <stop offset="100%" stopColor="#3b82f6" stopOpacity={0.05} />
                                        </linearGradient>
                                    </defs>
                                    <CartesianGrid strokeDasharray="3 3" className="stroke-muted" />
                                    <XAxis
                                        dataKey="name"
                                        tick={{ fontSize: 12 }}
                                        label={{ value: 'Mes', position: 'insideBottom', offset: -5 }}
                                    />
                                    <YAxis
                                        tick={{ fontSize: 12 }}
                                        label={{
                                            value: 'Número de citas',
                                            angle: -90,
                                            position: 'insideLeft',
                                        }}
                                    />
                                    <Tooltip />
                                    <Area
                                        type="monotone"
                                        dataKey="citas"
                                        stroke="#3b82f6"
                                        strokeWidth={2}
                                        fill="url(#areaGradient)"
                                        dot={{ r: 4, fill: '#3b82f6' }}
                                    />
                                </AreaChart>
                            </ResponsiveContainer>
                        </div>
                    </CardContent>
                </Card>

                <div className="grid gap-4 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Horas pico</CardTitle>
                            <CardDescription>Distribución por hora del día</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="h-[280px] w-full min-w-0">
                                <ResponsiveContainer width="100%" height={280} minHeight={280}>
                                    <BarChart data={peakChartData} margin={{ top: 10, right: 10, left: 0, bottom: 0 }}>
                                        <CartesianGrid strokeDasharray="3 3" className="stroke-muted" />
                                        <XAxis dataKey="name" tick={{ fontSize: 10 }} />
                                        <YAxis tick={{ fontSize: 12 }} />
                                        <Tooltip />
                                        <Bar dataKey="citas" fill="#8b5cf6" radius={[4, 4, 0, 0]} />
                                    </BarChart>
                                </ResponsiveContainer>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Distribución por día de la semana</CardTitle>
                            <CardDescription>Día de mayor actividad</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="h-[280px] w-full min-w-0">
                                <ResponsiveContainer width="100%" height={280} minHeight={280}>
                                    <PieChart>
                                        <Pie
                                            data={weekdayChartData}
                                            dataKey="citas"
                                            nameKey="name"
                                            cx="50%"
                                            cy="50%"
                                            outerRadius={90}
                                            label={(entry) => {
                                                const d = entry as unknown as { name: string; citas: number };
                                                return d.citas > 0 ? `${d.name}: ${d.citas}` : null;
                                            }}
                                        >
                                            {weekdayChartData.map((_, index) => (
                                                <Cell key={index} fill={weekdayChartData[index].fill} />
                                            ))}
                                        </Pie>
                                        <Tooltip />
                                    </PieChart>
                                </ResponsiveContainer>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <div className="grid gap-4 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Balance por calendario</CardTitle>
                            <CardDescription>Citas y horas por calendario</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b">
                                            <th className="py-2 text-left font-medium">Calendario</th>
                                            <th className="py-2 text-right font-medium">Citas</th>
                                            <th className="py-2 text-right font-medium">Horas</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {props.balanceByCalendar.length === 0 ? (
                                            <tr>
                                                <td colSpan={3} className="py-4 text-center text-muted-foreground">
                                                    Sin datos
                                                </td>
                                            </tr>
                                        ) : (
                                            props.balanceByCalendar.map((row) => (
                                                <tr key={row.calendar_id} className="border-b">
                                                    <td className="py-2">{row.calendar_name}</td>
                                                    <td className="py-2 text-right">{row.count}</td>
                                                    <td className="py-2 text-right">{row.hours.toFixed(1)}</td>
                                                </tr>
                                            ))
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Balance por usuario</CardTitle>
                            <CardDescription>Citas y horas por usuario asignado</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b">
                                            <th className="py-2 text-left font-medium">Usuario</th>
                                            <th className="py-2 text-right font-medium">Citas</th>
                                            <th className="py-2 text-right font-medium">Horas</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {props.balanceByUser.length === 0 ? (
                                            <tr>
                                                <td colSpan={3} className="py-4 text-center text-muted-foreground">
                                                    Sin datos
                                                </td>
                                            </tr>
                                        ) : (
                                            props.balanceByUser.map((row, idx) => (
                                                <tr key={row.user_id ?? `null-${idx}`} className="border-b">
                                                    <td className="py-2">{row.user_name}</td>
                                                    <td className="py-2 text-right">{row.count}</td>
                                                    <td className="py-2 text-right">{row.hours.toFixed(1)}</td>
                                                </tr>
                                            ))
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Promedio por año</CardTitle>
                        <CardDescription>Promedio de citas por año en el período</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="flex items-center gap-2">
                            <TrendingUp className="h-5 w-5 text-muted-foreground" />
                            <span className="text-2xl font-bold">{props.avgPerYear}</span>
                            <span className="text-muted-foreground text-sm">citas/año</span>
                        </div>
                    </CardContent>
                </Card>

                <div className="grid gap-4 sm:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Citas todo el día</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="flex items-center gap-2">
                                <Calendar className="h-5 w-5 text-muted-foreground" />
                                <span className="text-xl font-bold">{props.allDayCount}</span>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
