import { BrowserRouter, Routes, Route } from "react-router-dom";
import Home from "./pages/Home";
import Layout from "./components/Layout";
import AdminCalendar from "./pages/AdminPage";

export default function App() {
    return (
        <BrowserRouter>
            <Layout>
                <Routes>
                    <Route path="/" element={<Home />} />
                    <Route path="/admin/calendar" element={<AdminCalendar />} />
                </Routes>
            </Layout>
        </BrowserRouter>
    );
}

