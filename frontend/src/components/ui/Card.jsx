export default function Card({ className = '', hover = false, children, ...props }) {
  return (
    <div
      className={`rounded-xl border border-slate-200/80 bg-white shadow-card ${hover ? 'card-interactive cursor-pointer' : ''} ${className}`}
      {...props}
    >
      {children}
    </div>
  );
}

Card.Header = function CardHeader({ className = '', action, children }) {
  return (
    <div className={`flex items-center justify-between border-b border-slate-100 px-6 py-4 ${className}`}>
      <div className="flex-1">{children}</div>
      {action && <div className="ml-4 flex-shrink-0">{action}</div>}
    </div>
  );
};

Card.Body = function CardBody({ className = '', children }) {
  return <div className={`px-6 py-5 ${className}`}>{children}</div>;
};

Card.Footer = function CardFooter({ className = '', children }) {
  return (
    <div className={`border-t border-slate-100 px-6 py-4 ${className}`}>
      {children}
    </div>
  );
};
