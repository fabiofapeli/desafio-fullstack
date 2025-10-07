import { NavLink } from "react-router-dom";
import { useState } from "react";

const linkBase =
  "px-3 py-2 rounded-md text-sm font-medium transition hover:opacity-90";
const linkActive = "bg-white/20";
const linkInactive = "hover:bg-white/10";

export default function Header() {
  const [open, setOpen] = useState(false);

  return (
    <header className="fixed top-0 left-0 right-0 z-50 bg-[#F5BE01] shadow">
      <div className="mx-auto max-w-7xl px-4">
        <div className="flex h-16 items-center justify-between">
          {/* Logo */}
          <div className="flex items-center gap-2">
            <img src="logoInmediamPreto.png" />
          </div>

          {/* Desktop menu */}
          <nav className="hidden md:flex items-center gap-1 text-black/90">
            <NavLink
              to="/"
              className={({ isActive }) =>
                `${linkBase} ${isActive ? linkActive : linkInactive}`
              }
              end
            >
              Home
            </NavLink>
            <NavLink
              to="/history"
              className={({ isActive }) =>
                `${linkBase} ${isActive ? linkActive : linkInactive}`
              }
            >
              Histórico
            </NavLink>
            <NavLink
              to="/plans"
              className={({ isActive }) =>
                `${linkBase} ${isActive ? linkActive : linkInactive}`
              }
            >
              Planos
            </NavLink>
          </nav>

          {/* Mobile button */}
          <button
            className="md:hidden inline-flex items-center justify-center rounded-md p-2 hover:bg-black/10"
            onClick={() => setOpen((v) => !v)}
            aria-label="Abrir menu"
          >
            <svg viewBox="0 0 24 24" className="h-6 w-6" fill="none" stroke="currentColor">
              <path strokeWidth="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
          </button>
        </div>

        {/* Mobile menu */}
        {open && (
          <nav className="md:hidden pb-3">
            <div className="flex flex-col gap-1 text-black/90">
              <NavLink
                to="/"
                onClick={() => setOpen(false)}
                className={({ isActive }) =>
                  `${linkBase} ${isActive ? linkActive : linkInactive}`
                }
                end
              >
                Home
              </NavLink>
              <NavLink
                to="/history"
                onClick={() => setOpen(false)}
                className={({ isActive }) =>
                  `${linkBase} ${isActive ? linkActive : linkInactive}`
                }
              >
                Histórico
              </NavLink>
              <NavLink
                to="/plans"
                onClick={() => setOpen(false)}
                className={({ isActive }) =>
                  `${linkBase} ${isActive ? linkActive : linkInactive}`
                }
              >
                Planos
              </NavLink>
            </div>
          </nav>
        )}
      </div>
    </header>
  );
}
