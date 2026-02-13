import { Link } from "react-router-dom";

export default function Layout({ children }) {
    return (
        <div className="flex flex-col min-h-screen bg-gradient-to-b from-[#00121C] to-[#013954] text-white">
            <header className="h-[114px] flex flex-col sm:flex-row items-center justify-between py-4 px-6 border-b border-white/20 shadow-md">
                <Link to="/">
                    <img className="h-[42px]" src="/tap.png" alt="Logo" />
                </Link>
                <nav className="flex space-x-4 font-semibold">
                    <Link className="hover:text-amber-400 pt-2" to="/">Home</Link>
                    <Link className="hover:text-amber-400 pt-2" to="/admin/calendar">Calendrier</Link>
                </nav>
            </header>

            <main className="flex-1 p-6">{children}</main>

            <footer className="p-5 bg-white/5 text-center">
                Made with ❤️ by <a className="text-[#0086C4]" href="#">Juan</a>
            </footer>
        </div>
    );
}
