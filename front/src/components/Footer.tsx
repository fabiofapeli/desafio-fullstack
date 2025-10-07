export default function Footer() {
  return (
    <footer className="bg-[#1C1917] text-white/90">
      <div className="mx-auto max-w-7xl px-4 py-10 grid gap-8 md:grid-cols-3">
        <div>
          <div className="flex items-center gap-2">
            <img src="inmediam-logo-branco.png" />
          </div>
          <p className="mt-3 text-sm text-white/70">
            Aluguel sem fiador! Soluções digitais para locação com segurança e agilidade.
          </p>
        </div>

        <div>
          <h4 className="font-semibold mb-2">Contato</h4>
          <p className="text-sm text-white/80">sac@inmediam.com.br</p>
          <p className="text-sm text-white/80">+55 (31) 3611-3409</p>
        </div>

        <div>
          <h4 className="font-semibold mb-2">Links</h4>
          <ul className="text-sm text-white/80 space-y-1">
            <li>Aluguel sem fiador</li>
            <li>Como funciona</li>
            <li>Quem somos</li>
          </ul>
        </div>
      </div>
      <div className="border-t border-white/10 py-4 text-center text-xs text-white/60">
        © {new Date().getFullYear()} InMediam — Todos os direitos reservados.
      </div>
    </footer>
  );
}
