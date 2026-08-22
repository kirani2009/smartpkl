export default function Card({ className = '', children, ...props }) {
  return (
    <div
      className={`rounded-2xl border border-slate-200 bg-white shadow-sm ${className}`}
      {...props}
    >
      {children}
    </div>
  );
}

Card.Header = function CardHeader({ className = '', children }) {
  return (
    <div className={`border-b border-slate-200 px-6 py-4 ${className}`}>
      {children}
    </div>
  );
};

Card.Body = function CardBody({ className = '', children }) {
  return <div className={`px-6 py-4 ${className}`}>{children}</div>;
};

Card.Footer = function CardFooter({ className = '', children }) {
  return (
    <div className={`border-t border-slate-200 px-6 py-4 ${className}`}>
      {children}
    </div>
  );
};
