import { BrowserRouter, Routes, Route } from "react-router-dom";
import Layout from "./components/Layout";
import HomePage from "./pages/Home";
import HistoryPage from "./pages/History";
import PlansPage from "./pages/Plans";

export default function Router() {
  return (
    <BrowserRouter>
      <Routes>
        <Route element={<Layout />}>
          <Route path="/" element={<HomePage />} />
          <Route path="/history" element={<HistoryPage />} />
          <Route path="/plans" element={<PlansPage />} />
        </Route>
      </Routes>
    </BrowserRouter>
  );
}
