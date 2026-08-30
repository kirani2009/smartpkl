import { Routes, Route } from 'react-router-dom';
import { AuthProvider } from './contexts/AuthContext';
import ProtectedRoute from './components/ProtectedRoute';
import DashboardLayout from './components/layout/DashboardLayout';

// Public pages
import LandingPage from './pages/LandingPage';
import LoginPage from './pages/auth/LoginPage';
import RegisterPage from './pages/auth/RegisterPage';
import StudentInternships from './pages/student/StudentInternships';
import StudentInternshipDetail from './pages/student/StudentInternshipDetail';

// Student pages
import StudentDashboard from './pages/student/StudentDashboard';
import StudentProfile from './pages/student/StudentProfile';
import StudentApplications from './pages/student/StudentApplications';
import StudentInterviews from './pages/student/StudentInterviews';

// Teacher pages
import TeacherDashboard from './pages/teacher/TeacherDashboard';
import TeacherProfile from './pages/teacher/TeacherProfile';
import TeacherStudents from './pages/teacher/TeacherStudents';
import TeacherPartnerships from './pages/teacher/TeacherPartnerships';


// Company pages
import CompanyDashboard from './pages/company/CompanyDashboard';
import CompanyProfileSetup from './pages/company/CompanyProfileSetup';
import CompanyPartnerships from './pages/company/CompanyPartnerships';
import CompanyInternships from './pages/company/CompanyInternships';
import CompanyApplicants from './pages/company/CompanyApplicants';

// Admin pages
import AdminDashboard from './pages/admin/AdminDashboard';

export default function App() {
  return (
    <AuthProvider>
      <Routes>
        {/* Public routes */}
        <Route path="/" element={<LandingPage />} />
        <Route path="/login" element={<LoginPage />} />
        <Route path="/register" element={<RegisterPage />} />
        <Route path="/internships" element={<StudentInternships />} />
        <Route path="/internships/:id" element={<StudentInternshipDetail />} />

        {/* Dashboard routes — protected, role-based */}
        <Route
          element={
            <ProtectedRoute>
              <DashboardLayout />
            </ProtectedRoute>
          }
        >
          {/* Student */}
          <Route path="/student/dashboard" element={
            <ProtectedRoute allowedRoles={['student']}>
              <StudentDashboard />
            </ProtectedRoute>
          } />
          <Route path="/student/profile" element={
            <ProtectedRoute allowedRoles={['student']}>
              <StudentProfile />
            </ProtectedRoute>
          } />
          <Route path="/student/applications" element={
            <ProtectedRoute allowedRoles={['student']}>
              <StudentApplications />
            </ProtectedRoute>
          } />
          <Route path="/student/interviews" element={
            <ProtectedRoute allowedRoles={['student']}>
              <StudentInterviews />
            </ProtectedRoute>
          } />

          {/* Teacher */}
          <Route path="/teacher/dashboard" element={
            <ProtectedRoute allowedRoles={['teacher']}>
              <TeacherDashboard />
            </ProtectedRoute>
          } />
          <Route path="/teacher/profile" element={
            <ProtectedRoute allowedRoles={['teacher']}>
              <TeacherProfile />
            </ProtectedRoute>
          } />
          <Route path="/teacher/students" element={
            <ProtectedRoute allowedRoles={['teacher']}>
              <TeacherStudents />
            </ProtectedRoute>
          } />
          <Route path="/teacher/partnerships" element={
            <ProtectedRoute allowedRoles={['teacher']}>
              <TeacherPartnerships />
            </ProtectedRoute>
          } />


          {/* Company */}
          <Route path="/company/dashboard" element={
            <ProtectedRoute allowedRoles={['company']}>
              <CompanyDashboard />
            </ProtectedRoute>
          } />
          <Route path="/company/profile/setup" element={
            <ProtectedRoute allowedRoles={['company']}>
              <CompanyProfileSetup />
            </ProtectedRoute>
          } />
          <Route path="/company/partnerships" element={
            <ProtectedRoute allowedRoles={['company']}>
              <CompanyPartnerships />
            </ProtectedRoute>
          } />
          <Route path="/company/internships" element={
            <ProtectedRoute allowedRoles={['company']}>
              <CompanyInternships />
            </ProtectedRoute>
          } />
          <Route path="/company/applicants" element={
            <ProtectedRoute allowedRoles={['company']}>
              <CompanyApplicants />
            </ProtectedRoute>
          } />

          {/* Admin */}
          <Route path="/admin/dashboard" element={
            <ProtectedRoute allowedRoles={['admin']}>
              <AdminDashboard />
            </ProtectedRoute>
          } />
        </Route>
      </Routes>
    </AuthProvider>
  );
}
