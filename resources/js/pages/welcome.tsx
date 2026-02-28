import { Head, Link, usePage } from '@inertiajs/react';
import { motion } from 'framer-motion';
import { CheckCircle2, Car, ShieldCheck, Clock, MapPin, Phone, Mail, ArrowRight } from 'lucide-react';
import { dashboard, login } from '@/routes';
import { show as bookOffer } from '@/routes/enrollment';

interface Offer {
    id: number;
    name: string;
    description: string | null;
    price: string;
    type: 'primary' | 'addon';
    theory_lessons: number;
    driving_lessons: number;
    track_required: boolean;
    slippery_required: boolean;
}

export default function Welcome({ offers = [] }: { offers?: Offer[] }) {
    const { auth } = usePage().props;

    return (
        <div className="min-h-screen bg-background text-foreground selection:bg-primary selection:text-primary-foreground font-sans">
            <Head title="Velkommen | Køreskole" />

            {/* Navbar */}
            <header className="sticky top-0 z-50 w-full border-b border-border/40 bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60">
                <div className="container mx-auto flex h-16 items-center justify-between px-4 lg:px-8">
                    <div className="flex items-center gap-2">
                        <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-primary text-primary-foreground">
                            <Car size={20} />
                        </div>
                        <span className="text-xl font-bold tracking-tight">Køreskole Pro</span>
                    </div>

                    <nav className="hidden md:flex flex-1 items-center justify-center gap-8 text-sm font-medium">
                        <a href="#features" className="text-muted-foreground hover:text-primary transition-colors">Fordele</a>
                        <a href="#packages" className="text-muted-foreground hover:text-primary transition-colors">Pakker</a>
                        <a href="#about" className="text-muted-foreground hover:text-primary transition-colors">Om os</a>
                        <a href="#contact" className="text-muted-foreground hover:text-primary transition-colors">Kontakt</a>
                    </nav>

                    <div className="flex items-center gap-4">
                        {auth.user ? (
                            <Link
                                href={dashboard()}
                                className="inline-flex h-9 items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                            >
                                Kontrolpanel
                            </Link>
                        ) : (
                            <Link
                                href={login()}
                                className="inline-flex h-9 items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                            >
                                Log ind
                            </Link>
                        )}
                    </div>
                </div>
            </header>

            <main>
                {/* Hero Section */}
                <section className="relative overflow-hidden pt-24 pb-32 lg:pt-36 lg:pb-40">
                    <div className="absolute inset-0 bg-gradient-to-br from-primary/5 via-background to-accent/5 -z-10" />
                    <div className="container mx-auto px-4 lg:px-8 grid lg:grid-cols-2 gap-12 items-center">
                        <motion.div 
                            initial={{ opacity: 0, x: -30 }}
                            animate={{ opacity: 1, x: 0 }}
                            transition={{ duration: 0.6, delay: 0.2 }}
                            className="flex flex-col items-center text-center lg:items-start lg:text-left"
                        >
                            <h1 className="text-4xl font-extrabold tracking-tight sm:text-5xl md:text-6xl lg:text-7xl">
                                Bliv en sikker bilist med <br />
                                <span className="text-primary">Selvtillid</span>
                            </h1>
                            <p className="mt-6 max-w-[600px] text-lg text-muted-foreground sm:text-xl">
                                Dygtige kørelærere, moderne bilpark og et undervisningsforløb designet til at gøre dig til en sikker bilist. Start din rejse i dag.
                            </p>
                            <div className="mt-10 flex flex-col sm:flex-row gap-4 flex-wrap justify-center lg:justify-start">
                                <a href="#packages" className="inline-flex h-12 w-full sm:w-auto items-center justify-center rounded-md bg-primary px-8 text-base font-medium text-primary-foreground shadow transition-colors hover:bg-primary/90">
                                    Se pakker
                                </a>
                                <a href="#features" className="inline-flex h-12 w-full sm:w-auto items-center justify-center rounded-md border border-input bg-background px-8 text-base font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground hover:border-accent">
                                    Læs mere
                                </a>
                            </div>
                        </motion.div>
                        <motion.div 
                            initial={{ opacity: 0, scale: 0.9, y: 30 }}
                            animate={{ opacity: 1, scale: 1, y: 0 }}
                            transition={{ duration: 0.6, delay: 0.4 }}
                            className="relative mx-auto w-full max-w-[500px] lg:max-w-none mt-12 lg:mt-0"
                        >
                            <div className="relative aspect-square md:aspect-[4/3] lg:aspect-square overflow-hidden rounded-3xl shadow-2xl">
                                <img 
                                    src="/images/hero.png" 
                                    alt="Køreelev bag rattet iført et stort smil" 
                                    className="object-cover w-full h-full hover:scale-105 transition-transform duration-700"
                                />
                                <div className="absolute inset-0 rounded-3xl ring-1 ring-inset ring-black/10"></div>
                            </div>
                            
                            {/* Decorative background elements behind image */}
                            <div className="absolute -bottom-8 -left-8 h-32 w-32 rounded-full bg-primary/20 blur-3xl -z-10"></div>
                            <div className="absolute -top-8 -right-8 h-40 w-40 rounded-full bg-accent/20 blur-3xl -z-10"></div>
                        </motion.div>
                    </div>
                </section>

                {/* Features Section */}
                <section id="features" className="py-24 bg-card overflow-hidden">
                    <div className="container mx-auto px-4 lg:px-8">
                        <motion.div 
                            initial={{ opacity: 0, y: 20 }}
                            whileInView={{ opacity: 1, y: 0 }}
                            viewport={{ once: true, margin: "-100px" }}
                            transition={{ duration: 0.5 }}
                            className="text-center mb-16"
                        >
                            <h2 className="text-3xl font-bold tracking-tight sm:text-4xl">Hvorfor vælge os?</h2>
                            <p className="mt-4 text-lg text-muted-foreground">Alt hvad du har brug for for at bestå din køreprøve og mere til.</p>
                        </motion.div>
                        <div className="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                            <motion.div 
                                initial={{ opacity: 0, y: 20 }}
                                whileInView={{ opacity: 1, y: 0 }}
                                viewport={{ once: true, margin: "-50px" }}
                                transition={{ duration: 0.5, delay: 0.1 }}
                                className="flex flex-col items-center text-center p-6 rounded-2xl bg-background border border-border/50 shadow-sm transition-all hover:shadow-md hover:border-primary/20 hover:-translate-y-1"
                            >
                                <div className="h-14 w-14 rounded-full bg-primary/10 flex items-center justify-center mb-6 text-primary">
                                    <ShieldCheck size={28} />
                                </div>
                                <h3 className="text-xl font-bold mb-3">Høj beståelsesprocent</h3>
                                <p className="text-muted-foreground">Vores skræddersyede undervisningsmetoder sikrer, at du er fuldt forberedt til at bestå både teori- og køreprøve.</p>
                            </motion.div>
                            <motion.div 
                                initial={{ opacity: 0, y: 20 }}
                                whileInView={{ opacity: 1, y: 0 }}
                                viewport={{ once: true, margin: "-50px" }}
                                transition={{ duration: 0.5, delay: 0.2 }}
                                className="flex flex-col items-center text-center p-6 rounded-2xl bg-background border border-border/50 shadow-sm transition-all hover:shadow-md hover:border-primary/20 hover:-translate-y-1"
                            >
                                <div className="h-14 w-14 rounded-full bg-accent/10 flex items-center justify-center mb-6 text-accent">
                                    <Car size={28} />
                                </div>
                                <h3 className="text-xl font-bold mb-3">Moderne biler</h3>
                                <p className="text-muted-foreground">Lær at køre i sikre, letkørte og miljøvenlige biler, der er udstyret med de nyeste sikkerhedsfunktioner.</p>
                            </motion.div>
                            <motion.div 
                                initial={{ opacity: 0, y: 20 }}
                                whileInView={{ opacity: 1, y: 0 }}
                                viewport={{ once: true, margin: "-50px" }}
                                transition={{ duration: 0.5, delay: 0.3 }}
                                className="flex flex-col items-center text-center p-6 rounded-2xl bg-background border border-border/50 shadow-sm transition-all hover:shadow-md hover:border-primary/20 hover:-translate-y-1"
                            >
                                <div className="h-14 w-14 rounded-full bg-primary/10 flex items-center justify-center mb-6 text-primary">
                                    <Clock size={28} />
                                </div>
                                <h3 className="text-xl font-bold mb-3">Fleksibel planlægning</h3>
                                <p className="text-muted-foreground">Book dine køre- og teoritimer på tidspunkter, der passer til din travle hverdag via vores online portal.</p>
                            </motion.div>
                        </div>
                    </div>
                </section>

                {/* Packages Section */}
                <section id="packages" className="py-24 bg-background overflow-hidden">
                    <div className="container mx-auto px-4 lg:px-8">
                        <motion.div 
                            initial={{ opacity: 0, y: 20 }}
                            whileInView={{ opacity: 1, y: 0 }}
                            viewport={{ once: true, margin: "-100px" }}
                            transition={{ duration: 0.5 }}
                            className="text-center mb-16"
                        >
                            <h2 className="text-3xl font-bold tracking-tight sm:text-4xl">Vores pakker</h2>
                            <p className="mt-4 text-lg text-muted-foreground">Gennemskuelige priser uden skjulte gebyrer.</p>
                        </motion.div>
                        <div className="grid gap-8 md:grid-cols-2 max-w-5xl mx-auto">
                            {offers.length > 0 ? offers.map((offer, index) => (
                                <motion.div 
                                    key={offer.id}
                                    initial={{ opacity: 0, y: 30 }}
                                    whileInView={{ opacity: 1, y: 0 }}
                                    viewport={{ once: true, margin: "-50px" }}
                                    transition={{ duration: 0.5, delay: index * 0.1 }}
                                    className="relative flex flex-col p-8 rounded-3xl border border-border bg-card shadow-sm hover:shadow-lg transition-shadow"
                                >
                                    {offer.type === 'primary' && (
                                        <div className="absolute top-0 right-8 -translate-y-1/2 rounded-full bg-primary px-4 py-1 text-sm font-semibold text-primary-foreground inline-flex items-center gap-1">
                                            Mest populære
                                        </div>
                                    )}
                                    <div className="mb-6">
                                        <h3 className="text-2xl font-bold">{offer.name}</h3>
                                        <p className="text-muted-foreground mt-2">{offer.description}</p>
                                        <div className="mt-6 flex items-baseline text-5xl font-extrabold pb-2">
                                            {Number(offer.price).toLocaleString('da-DK')}
                                            <span className="ml-1 text-xl font-medium text-muted-foreground">DKK</span>
                                        </div>
                                        {offer.type === 'addon' && <p className="text-sm text-muted-foreground mt-2">Ekstra tillægspakke</p>}
                                    </div>
                                    <ul className="mb-8 space-y-4 flex-1">
                                        {offer.theory_lessons > 0 && (
                                            <li className="flex items-center gap-3">
                                                <CheckCircle2 className="text-primary h-5 w-5 shrink-0" />
                                                <span>{offer.theory_lessons} Teoritimer</span>
                                            </li>
                                        )}
                                        {offer.driving_lessons > 0 && (
                                            <li className="flex items-center gap-3">
                                                <CheckCircle2 className="text-primary h-5 w-5 shrink-0" />
                                                <span>{offer.driving_lessons} Kørelektioner (45 min)</span>
                                            </li>
                                        )}
                                        {offer.track_required && (
                                            <li className="flex items-center gap-3">
                                                <CheckCircle2 className="text-primary h-5 w-5 shrink-0" />
                                                <span>Manøvrebane</span>
                                            </li>
                                        )}
                                        {offer.slippery_required && (
                                            <li className="flex items-center gap-3">
                                                <CheckCircle2 className="text-primary h-5 w-5 shrink-0" />
                                                <span>Køreteknisk anlæg (Glatbane)</span>
                                            </li>
                                        )}
                                    </ul>
                                    {offer.type === 'primary' ? (
                                        <Link href={bookOffer.url(offer.id)} className="mt-auto inline-flex h-12 w-full items-center justify-center rounded-xl px-6 text-base font-medium transition-colors bg-primary text-primary-foreground hover:bg-primary/90">
                                            Tilmeld dig nu <ArrowRight className="ml-2 h-5 w-5" />
                                        </Link>
                                    ) : (
                                        <a href="#contact" className="mt-auto inline-flex h-12 w-full items-center justify-center rounded-xl px-6 text-base font-medium transition-colors border border-input bg-background hover:bg-accent hover:text-accent-foreground hover:border-accent">
                                            Kontakt os
                                        </a>
                                    )}
                                </motion.div>
                            )) : (
                                <div className="col-span-full text-center text-muted-foreground py-12">
                                    Ingen pakker tilgængelige i øjeblikket. Tjek venligst tilbage senere.
                                </div>
                            )}
                        </div>
                    </div>
                </section>

                {/* CTA Section */}
                <section className="py-24 bg-primary text-primary-foreground relative overflow-hidden">
                    <div className="absolute inset-0 opacity-10 bg-[radial-gradient(ellipse_at_center,_var(--tw-gradient-stops))] from-white to-transparent" />
                    <motion.div 
                        initial={{ opacity: 0, scale: 0.95 }}
                        whileInView={{ opacity: 1, scale: 1 }}
                        viewport={{ once: true }}
                        transition={{ duration: 0.6 }}
                        className="container relative mx-auto px-4 lg:px-8 text-center max-w-3xl"
                    >
                        <h2 className="text-3xl font-bold tracking-tight sm:text-4xl mb-6">Klar til at sætte dig bag rattet?</h2>
                        <p className="text-lg text-primary-foreground/80 mb-10">Slut dig til hundredevis af succesfulde elever, der har bestået deres køreprøve hos os. Start dit strukturerede læringsforløb i dag.</p>
                        <Link href={login()} className="inline-flex h-14 items-center justify-center rounded-full bg-white px-8 text-lg font-bold text-primary shadow-lg transition-transform hover:scale-105 active:scale-95">
                            Kom i gang nu
                        </Link>
                    </motion.div>
                </section>
            </main>

            {/* Footer */}
            <footer id="contact" className="border-t border-border bg-card py-12 overflow-hidden">
                <motion.div 
                    initial={{ opacity: 0, y: 20 }}
                    whileInView={{ opacity: 1, y: 0 }}
                    viewport={{ once: true }}
                    transition={{ duration: 0.5 }}
                    className="container mx-auto px-4 lg:px-8 grid gap-8 md:grid-cols-3"
                >
                    <div>
                        <div className="flex items-center gap-2 mb-6">
                            <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-primary text-primary-foreground">
                                <Car size={20} />
                            </div>
                            <span className="text-xl font-bold tracking-tight">Køreskole Pro</span>
                        </div>
                        <p className="text-muted-foreground text-sm max-w-xs">Vi forbinder motiverede elever med dygtige kørelærere. Vi gør det nemt, sikkert og sjovt at tage kørekort.</p>
                    </div>
                    <div>
                        <h4 className="font-semibold mb-6">Kontakt os</h4>
                        <ul className="space-y-4 text-sm text-muted-foreground">
                            <li className="flex items-center gap-3">
                                <MapPin size={16} className="text-primary" />
                                <span>Køregade 123, København</span>
                            </li>
                            <li className="flex items-center gap-3">
                                <Phone size={16} className="text-primary" />
                                <span>+45 12 34 56 78</span>
                            </li>
                            <li className="flex items-center gap-3">
                                <Mail size={16} className="text-primary" />
                                <span>hello@koereskole-pro.dk</span>
                            </li>
                        </ul>
                    </div>
                    <div>
                        <h4 className="font-semibold mb-6">Juridisk</h4>
                        <ul className="space-y-4 text-sm text-muted-foreground">
                            <li><a href="#" className="hover:text-primary transition-colors">Handelsbetingelser</a></li>
                            <li><a href="#" className="hover:text-primary transition-colors">Privatlivspolitik</a></li>
                            <li><a href="#" className="hover:text-primary transition-colors">Cookiepolitik</a></li>
                        </ul>
                    </div>
                </motion.div>
                <div className="container mx-auto px-4 lg:px-8 mt-12 pt-8 border-t border-border text-center text-sm text-muted-foreground">
                    &copy; {new Date().getFullYear()} Køreskole Pro. Alle rettigheder forbeholdes.
                </div>
            </footer>
        </div>
    );
}
